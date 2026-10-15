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

            $stringCount = $inverterCount;
            $panelsPerString = (int) ceil($requiredPanelCount / $stringCount);
            $stringPowerW = $panelsPerString * $panelPowerW;

            if ($stringPowerW > $panelLimitW) {
                throw ValidationException::withMessages([
                    'solar_panel_equipment_id' => sprintf(
                        'Cada string tendría %.2f W, por encima del límite MPPT de %.2f W. Selecciona otro inversor o panel.',
                        $stringPowerW,
                        $panelLimitW,
                    ),
                ]);
            }

            $panelCount = $panelsPerString * $stringCount;
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

        $project->systemDesign()->update([
            'panel_count' => null,
            'inverter_count' => null,
            'battery_count' => null,
            'panels_per_string' => null,
            'array_power_w' => null,
        ]);
    }
}
