<?php

namespace App\Services;

use App\Models\Appliance;
use App\Models\ElectricalEquipment;
use App\Models\SolarProject;
use App\Models\SolarProjectAppliance;
use App\Models\SolarProjectSystemDesign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolarProjectCalculator
{
    public function addAppliance(SolarProject $project, Appliance $appliance, array $data): SolarProjectAppliance
    {
        return DB::transaction(function () use ($project, $appliance, $data): SolarProjectAppliance {
            $hourlyEnergyWh = (float) $appliance->energy_consumption_wh;
            $quantityTotal = (int) $data['quantity_total'];
            $quantityNight = (int) $data['quantity_night'];
            $usageHoursTotal = (float) $data['usage_hours_total'];
            $usageHoursNight = (float) $data['usage_hours_night'];

            $item = $project->applianceItems()->create([
                'appliance_id' => $appliance->id,
                'appliance_energy_consumption_wh' => $hourlyEnergyWh,
                'quantity_total' => $quantityTotal,
                'quantity_night' => $quantityNight,
                'usage_hours_total' => $usageHoursTotal,
                'usage_hours_night' => $usageHoursNight,
                'daily_energy_wh' => round($hourlyEnergyWh * $quantityTotal * $usageHoursTotal, 2),
                'night_energy_wh' => round($hourlyEnergyWh * $quantityNight * $usageHoursNight, 2),
                'peak_power_w' => round($hourlyEnergyWh * $quantityTotal, 2),
            ]);

            $this->refreshProjectTotals($project);

            return $item;
        });
    }

    public function removeAppliance(SolarProjectAppliance $item): void
    {
        DB::transaction(function () use ($item): void {
            $project = $item->project;
            $item->delete();
            $this->refreshProjectTotals($project);
        });
    }

    public function saveSystemDesign(SolarProject $project, array $data): SolarProjectSystemDesign
    {
        return DB::transaction(function () use ($project, $data): SolarProjectSystemDesign {
            $project->refresh();
            $panel = ElectricalEquipment::query()->with('category', 'solarPanelSpec')->findOrFail($data['solar_panel_equipment_id']);
            $inverter = ElectricalEquipment::query()->with('category', 'inverterSpec')->findOrFail($data['inverter_equipment_id']);
            $battery = ElectricalEquipment::query()->with('category', 'batterySpec')->findOrFail($data['battery_equipment_id']);

            if ($panel->category->slug !== 'paneles-solares' || $panel->solarPanelSpec === null) {
                throw ValidationException::withMessages(['solar_panel_equipment_id' => 'Selecciona un panel solar con ficha técnica válida.']);
            }

            if ($inverter->category->slug !== 'inversores' || $inverter->inverterSpec === null) {
                throw ValidationException::withMessages(['inverter_equipment_id' => 'Selecciona un inversor con ficha técnica válida.']);
            }

            if ($battery->category->slug !== 'baterias' || $battery->batterySpec === null) {
                throw ValidationException::withMessages(['battery_equipment_id' => 'Selecciona una batería con ficha técnica válida.']);
            }

            if (abs((float) $battery->batterySpec->voltage_v - (float) $inverter->inverterSpec->battery_charge_voltage_v) > 0.001) {
                throw ValidationException::withMessages(['battery_equipment_id' => 'El voltaje nominal de la batería debe coincidir con el voltaje de carga del inversor.']);
            }

            $panelPowerW = (float) $panel->solarPanelSpec->max_power_w;
            $panelLimitW = (float) $inverter->inverterSpec->mppt_max_power_w;
            $totalOversizing = (float) $data['total_oversizing_percent'] / 100;
            $nightOversizing = (float) $data['night_oversizing_percent'] / 100;
            $maxBatteryDischarge = (float) $data['max_battery_discharge_percent'] / 100;
            $sunHours = (float) $project->solar_hours_per_day;

            $requiredPanelCount = (int) ceil(
                ((float) $project->total_consumption_wh_per_day * (1 + $totalOversizing)) / ($panelPowerW * $sunHours),
            );
            $inverterCount = (int) ceil(
                ((float) $project->maximum_hourly_power_w * (1 + $totalOversizing)) / (float) $inverter->inverterSpec->max_output_power_w,
            );
            $batteryEnergyWh = (float) $battery->batterySpec->capacity_ah * (float) $battery->batterySpec->voltage_v;
            $batteryCount = (int) ceil(
                ((float) $project->night_consumption_wh_per_day * (1 + $nightOversizing)) / ($batteryEnergyWh * $maxBatteryDischarge),
            );

            if ($panelPowerW > $panelLimitW) {
                throw ValidationException::withMessages([
                    'solar_panel_equipment_id' => sprintf(
                        'Un solo panel (%.2f W) supera el límite MPPT del inversor (%.2f W). Selecciona otro inversor o panel.',
                        $panelPowerW,
                        $panelLimitW,
                    ),
                ]);
            }

            $topology = $this->topologyForPanelCount($requiredPanelCount, $inverterCount, $panelPowerW, $panelLimitW);
            $inverterCount = $topology['inverter_count'];
            $stringCount = $topology['string_count'];
            $panelsPerString = $topology['panels_per_string'];
            $panelCount = $topology['panel_count'];
            $previousDesign = $project->systemDesign;

            if ($previousDesign === null || (int) $previousDesign->panels_per_string !== $panelsPerString || (int) $previousDesign->inverter_count !== $inverterCount) {
                $project->stringRows()->delete();
            }

            $design = $project->systemDesign()->updateOrCreate([], [
                'solar_panel_equipment_id' => $panel->id,
                'inverter_equipment_id' => $inverter->id,
                'battery_equipment_id' => $battery->id,
                'total_oversizing_percent' => $data['total_oversizing_percent'],
                'night_oversizing_percent' => $data['night_oversizing_percent'],
                'max_battery_discharge_percent' => $data['max_battery_discharge_percent'],
                'panel_count' => $panelCount,
                'inverter_count' => $inverterCount,
                'battery_count' => $batteryCount,
                'panels_per_string' => $panelsPerString,
                'array_power_w' => round($panelCount * $panelPowerW, 2),
            ]);

            $project->forceFill(['solar_string_count' => $stringCount])->save();

            return $design;
        });
    }

    /**
     * @return array{panel_count: int, inverter_count: int, string_count: int, panels_per_string: int}
     */
    public function topologyForPanelCount(int $requiredPanelCount, int $minimumInverterCount, float $panelPowerW, float $panelLimitW): array
    {
        if ($requiredPanelCount < 1 || $panelPowerW > $panelLimitW) {
            throw ValidationException::withMessages([
                'lines' => 'La cantidad de paneles no es compatible con el límite MPPT del inversor.',
            ]);
        }

        $inverterCount = max(1, $minimumInverterCount);

        while (ceil($requiredPanelCount / $inverterCount) * $panelPowerW > $panelLimitW) {
            $inverterCount++;
        }

        $panelsPerString = (int) ceil($requiredPanelCount / $inverterCount);
        $panelCount = $panelsPerString * $inverterCount;

        return [
            'panel_count' => $panelCount,
            'inverter_count' => $inverterCount,
            'string_count' => $inverterCount,
            'panels_per_string' => $panelsPerString,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function explainDesign(SolarProject $project, SolarProjectSystemDesign $design): array
    {
        $design->loadMissing('solarPanel.solarPanelSpec', 'inverter.inverterSpec', 'battery.batterySpec');
        $panelSpec = $design->solarPanel->solarPanelSpec;
        $inverterSpec = $design->inverter->inverterSpec;
        $batterySpec = $design->battery->batterySpec;
        $totalOversizing = (float) $design->total_oversizing_percent / 100;
        $nightOversizing = (float) $design->night_oversizing_percent / 100;
        $discharge = (float) $design->max_battery_discharge_percent / 100;

        $requiredPanels = (int) ceil(((float) $project->total_consumption_wh_per_day * (1 + $totalOversizing)) / ((float) $panelSpec->max_power_w * (float) $project->solar_hours_per_day));
        $inverterByOutput = max(1, (int) ceil(((float) $project->maximum_hourly_power_w * (1 + $totalOversizing)) / (float) $inverterSpec->max_output_power_w));
        $batteryEnergyWh = (float) $batterySpec->capacity_ah * (float) $batterySpec->voltage_v;
        $format = fn (float $value): string => number_format($value, 2, ',', '.');

        $panels = sprintf(
            '%s: se necesitan %d paneles; la demanda de %s Wh/día con %s%% de sobredimensionamiento y %s horas solares requiere %d paneles',
            $design->solarPanel->name,
            $design->panel_count,
            $format((float) $project->total_consumption_wh_per_day),
            $format((float) $design->total_oversizing_percent),
            $format((float) $project->solar_hours_per_day),
            $requiredPanels,
        );
        $panels .= $design->panel_count > $requiredPanels
            ? sprintf(', y se agregaron %d para que los %d strings tengan %d paneles cada uno.', $design->panel_count - $requiredPanels, $design->inverter_count, $design->panels_per_string)
            : '.';

        $inverters = sprintf(
            '%s: se necesitan %d inversores; por potencia de salida (%s W pico con sobredimensionamiento sobre %s W por inversor) bastarían %d',
            $design->inverter->name,
            $design->inverter_count,
            $format((float) $project->maximum_hourly_power_w * (1 + $totalOversizing)),
            $format((float) $inverterSpec->max_output_power_w),
            $inverterByOutput,
        );
        $inverters .= $design->inverter_count > $inverterByOutput
            ? sprintf(', pero se aumentó para que cada string no supere el límite MPPT de %s W.', $format((float) $inverterSpec->mppt_max_power_w))
            : '.';

        $batteries = sprintf(
            '%s: se necesitan %d baterías; el consumo nocturno de %s Wh/día con %s%% de sobredimensionamiento y %s%% de descarga máxima, sobre %s Wh por batería (%s Ah × %s V).',
            $design->battery->name,
            $design->battery_count,
            $format((float) $project->night_consumption_wh_per_day),
            $format($nightOversizing * 100),
            $format($discharge * 100),
            $format($batteryEnergyWh),
            $format((float) $batterySpec->capacity_ah),
            $format((float) $batterySpec->voltage_v),
        );

        $strings = sprintf(
            'Strings: se forman %d strings, uno por inversor, con %d paneles cada uno (%s W por string).',
            $design->inverter_count,
            $design->panels_per_string,
            $format($design->panels_per_string * (float) $panelSpec->max_power_w),
        );

        return compact('panels', 'inverters', 'batteries', 'strings');
    }

    private function refreshProjectTotals(SolarProject $project): void
    {
        $totals = $project->applianceItems()
            ->selectRaw('COUNT(*) AS item_count, COALESCE(SUM(daily_energy_wh), 0) AS total_wh, COALESCE(SUM(night_energy_wh), 0) AS night_wh, COALESCE(SUM(peak_power_w), 0) AS peak_w')
            ->first();

        $project->forceFill([
            'total_consumption_wh_per_day' => round((float) $totals->total_wh, 2),
            'night_consumption_wh_per_day' => round((float) $totals->night_wh, 2),
            'maximum_hourly_power_w' => (int) $totals->item_count > 0 ? round((float) $totals->peak_w, 2) : null,
            'solar_string_count' => null,
        ])->save();

        $project->stringRows()->delete();

        $project->systemDesign()->update([
            'panel_count' => null,
            'inverter_count' => null,
            'battery_count' => null,
            'panels_per_string' => null,
            'array_power_w' => null,
        ]);
    }
}
