<?php

namespace App\Services;

use App\Models\ElectricalEquipment;
use App\Models\InstallationMaterial;
use App\Models\SolarProject;
use App\Models\SolarProjectQuote;
use App\Models\SolarProjectSystemDesign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolarProjectQuoteBuilder
{
    private const MATERIAL_LABELS = [
        'rails' => 'Riel estructura solar',
        'rail_unions' => 'Uniones de riel',
        'ground_connectors' => 'Conectores de tierra',
        'mc4_connectors' => 'Conectores MC4',
        'end_clamps' => 'End clamp',
        'mid_clamps' => 'Mid clamp',
    ];

    private const PROTECTION_KEYS = ['dc_breaker', 'ac_input', 'ac_output', 'dps'];

    public function __construct(
        private SolarProjectMaterialCalculator $materials,
        private SolarProjectProtectionRecommender $protections,
        private SolarProjectCalculator $calculator,
    ) {}

    /**
     * Copia congelada de cargas y dimensionamiento; la ficha PDF se arma solo con ella.
     *
     * @return array<string, mixed>
     */
    public function snapshot(SolarProject $project, SolarProjectSystemDesign $design, ?array $layout = null): array
    {
        $project->refresh();
        $design->loadMissing('solarPanel.solarPanelSpec', 'inverter.inverterSpec', 'battery.batterySpec');
        $layout ??= $this->materials->layout($project, $design);
        $panelSpec = $design->solarPanel->solarPanelSpec;
        $inverterSpec = $design->inverter->inverterSpec;
        $batterySpec = $design->battery->batterySpec;

        return [
            'captured_at' => now()->toIso8601String(),
            'project' => [
                'name' => $project->name,
                'description' => $project->description,
                'warranty' => $project->project_warranty,
                'solar_hours_per_day' => (float) $project->solar_hours_per_day,
                'latitude' => $project->latitude !== null ? (float) $project->latitude : null,
                'longitude' => $project->longitude !== null ? (float) $project->longitude : null,
            ],
            'consumption' => [
                'total_wh' => (float) $project->total_consumption_wh_per_day,
                'night_wh' => (float) $project->night_consumption_wh_per_day,
                'peak_w' => (float) $project->maximum_hourly_power_w,
            ],
            'loads' => $project->applianceItems()->with('appliance')->orderBy('id')->get()->map(fn ($item) => [
                'name' => $item->appliance->name,
                'hourly_wh' => (float) $item->appliance_energy_consumption_wh,
                'quantity_total' => $item->quantity_total,
                'quantity_night' => $item->quantity_night,
                'hours_total' => (float) $item->usage_hours_total,
                'hours_night' => (float) $item->usage_hours_night,
                'daily_wh' => (float) $item->daily_energy_wh,
                'night_wh' => (float) $item->night_energy_wh,
                'peak_w' => (float) $item->peak_power_w,
            ])->all(),
            'design' => [
                'panel' => ['name' => $design->solarPanel->name, 'power_w' => (float) $panelSpec->max_power_w],
                'inverter' => ['name' => $design->inverter->name, 'output_w' => (float) $inverterSpec->max_output_power_w, 'mppt_w' => (float) $inverterSpec->mppt_max_power_w],
                'battery' => ['name' => $design->battery->name, 'capacity_ah' => (float) $batterySpec->capacity_ah, 'voltage_v' => (float) $batterySpec->voltage_v],
                'total_oversizing_percent' => (float) $design->total_oversizing_percent,
                'night_oversizing_percent' => (float) $design->night_oversizing_percent,
                'max_battery_discharge_percent' => (float) $design->max_battery_discharge_percent,
                'panel_count' => $design->panel_count,
                'inverter_count' => $design->inverter_count,
                'battery_count' => $design->battery_count,
                'panels_per_string' => $design->panels_per_string,
                'string_count' => (int) $design->inverter_count,
                'array_power_w' => (float) $design->array_power_w,
                'explanations' => $this->calculator->explainDesign($project, $design),
                'string_layout' => $layout,
            ],
            'diagram' => [
                'panel_count' => (int) $design->panel_count,
                'inverter_count' => (int) $design->inverter_count,
                'battery_count' => (int) $design->battery_count,
                'string_count' => (int) $design->inverter_count,
                'panels_per_string' => (int) $design->panels_per_string,
                'string_layout' => $layout,
            ],
            'materials' => $this->materials->calculate($layout),
            'protections' => array_map(function (array $protection): array {
                $spec = $protection['item']?->dcBreakerSpec ?? $protection['item']?->acBreakerSpec;

                return [
                    'title' => $protection['title'],
                    'quantity' => $protection['quantity'],
                    'recommendation' => $protection['recommendation'],
                    'item' => $protection['item']?->name,
                    'poles' => $spec?->poles,
                    'voltage_v' => $spec !== null ? (float) ($spec->max_operating_voltage_v ?? $spec->operating_voltage_v) : null,
                    'current_a' => $spec !== null ? (float) $spec->rated_current_a : null,
                ];
            }, $this->protections->recommend($design)),
        ];
    }

    /**
     * Líneas que salen del dimensionamiento, con el ítem del catálogo ya seleccionado.
     *
     * @return array{lines: list<array{key: string, concept: string, item: string, name: string, quantity: int, unit_price: float}>, missing: list<string>}
     */
    public function autoLines(SolarProject $project, SolarProjectSystemDesign $design, ?array $layout = null): array
    {
        $design->loadMissing('solarPanel', 'inverter', 'battery');
        $lines = [];
        $missing = [];

        $add = function (string $key, string $concept, ElectricalEquipment|InstallationMaterial|null $item, int $quantity) use (&$lines, &$missing): void {
            if ($item === null) {
                $missing[] = $concept;

                return;
            }

            $lines[] = [
                'key' => $key,
                'concept' => $concept,
                'item' => ($item instanceof ElectricalEquipment ? 'equipment:' : 'material:').$item->id,
                'name' => $item->name,
                'quantity' => $quantity,
                'unit_price' => (float) $item->price,
            ];
        };

        $add('panel', 'Paneles solares', $design->solarPanel, (int) $design->panel_count);
        $add('inverter', 'Inversores', $design->inverter, (int) $design->inverter_count);
        $add('battery', 'Baterías', $design->battery, (int) $design->battery_count);

        foreach ($this->protections->recommend($design) as $index => $protection) {
            $add(self::PROTECTION_KEYS[$index], $protection['title'], $protection['item'], $protection['quantity']);
        }

        $quantities = $this->materials->calculate($layout ?? $this->materials->layout($project, $design));
        $catalog = $this->materials->catalogItems();

        foreach (self::MATERIAL_LABELS as $key => $label) {
            $add($key, $label, $catalog[$key], $quantities[$key]);
        }

        return ['lines' => $lines, 'missing' => $missing];
    }

    /**
     * @param  list<array{item: string, line_key?: ?string, adjustment: int|string}>  $lines
     * @return array{design: SolarProjectSystemDesign, layout: array<int, list<int>>, auto_lines: list<array<string, mixed>>, effective_adjustments: array<string, int>, requested_panel_count: int, diagram: array<string, mixed>}
     */
    public function quoteScenario(SolarProject $project, SolarProjectSystemDesign $design, array $lines): array
    {
        $design->loadMissing('solarPanel.solarPanelSpec', 'inverter.inverterSpec', 'battery.batterySpec');
        $baseLines = collect($this->autoLines($project, $design)['lines'])->keyBy('key');
        $panelLine = $baseLines->get('panel');
        $panelAdjustment = $this->adjustmentFor($lines, 'panel', $panelLine['item'] ?? null);
        $requestedPanelCount = (int) $design->panel_count + $panelAdjustment;

        if ($requestedPanelCount < 1) {
            $panelIndex = collect($lines)->search(fn (array $line) => ($line['line_key'] ?? null) === 'panel');
            throw ValidationException::withMessages([
                'lines.'.($panelIndex === false ? 0 : $panelIndex).'.adjustment' => 'La cantidad final de paneles debe ser al menos 1.',
            ]);
        }

        $inverterSpec = $design->inverter->inverterSpec;
        $minimumInverterCount = max(1, (int) ceil(
            (float) $project->maximum_hourly_power_w
            * (1 + (float) $design->total_oversizing_percent / 100)
            / (float) $inverterSpec->max_output_power_w,
        ));
        $topology = $this->calculator->topologyForPanelCount(
            $requestedPanelCount,
            $minimumInverterCount,
            (float) $design->solarPanel->solarPanelSpec->max_power_w,
            (float) $inverterSpec->mppt_max_power_w,
        );

        $scenarioDesign = clone $design;
        $scenarioDesign->setAttribute('panel_count', $topology['panel_count']);
        $scenarioDesign->setAttribute('inverter_count', $topology['inverter_count']);
        $scenarioDesign->setAttribute('panels_per_string', $topology['panels_per_string']);
        $scenarioDesign->setAttribute('array_power_w', round($topology['panel_count'] * (float) $design->solarPanel->solarPanelSpec->max_power_w, 2));

        $layout = $topology['panel_count'] === (int) $design->panel_count
            && $topology['inverter_count'] === (int) $design->inverter_count
            && $topology['panels_per_string'] === (int) $design->panels_per_string
            ? $this->materials->layout($project, $design)
            : array_fill(1, $topology['string_count'], [$topology['panels_per_string']]);

        $autoLines = $this->autoLines($project, $scenarioDesign, $layout)['lines'];
        $effectiveAdjustments = [];

        foreach ($autoLines as $line) {
            $effectiveAdjustments[$line['key']] = $line['quantity'] - (int) ($baseLines->get($line['key'])['quantity'] ?? 0);
        }

        $protections = collect($this->protections->recommend($scenarioDesign));
        $diagram = [
            'design' => $scenarioDesign,
            'stringLayout' => $layout,
            'dcProtection' => $protections->firstWhere('title', 'Breakers DC para strings'),
            'acInputProtection' => $protections->firstWhere('title', 'Breakers AC de entrada'),
            'acOutputProtection' => $protections->firstWhere('title', 'Breakers AC de salida'),
        ];

        return [
            ...$diagram,
            'diagram' => $diagram,
            'layout' => $layout,
            'auto_lines' => $autoLines,
            'effective_adjustments' => $effectiveAdjustments,
            'requested_panel_count' => $requestedPanelCount,
        ];
    }

    /**
     * Ítems activos que se pueden agregar a mano, agrupados para el selector.
     *
     * @return array<string, array<string, array{name: string, price: float}>>
     */
    public function itemOptions(): array
    {
        $groups = [];

        foreach (ElectricalEquipment::active()->with('category')->orderBy('name')->get() as $equipment) {
            $groups[$equipment->category->name]['equipment:'.$equipment->id] = ['name' => $equipment->name, 'price' => (float) $equipment->price];
        }

        foreach (InstallationMaterial::active()->orderBy('name')->get() as $material) {
            $groups['Materiales de instalación']['material:'.$material->id] = ['name' => $material->name, 'price' => (float) $material->price];
        }

        return $groups;
    }

    /**
     * @return array{name: string, price: float}|null
     */
    public function describeItem(string $item): ?array
    {
        $found = $this->findItem($item);

        return $found === null ? null : ['name' => $found->name, 'price' => (float) $found->price];
    }

    public function findItem(string $item): ElectricalEquipment|InstallationMaterial|null
    {
        [$type, $id] = array_pad(explode(':', $item, 2), 2, '');

        return match ($type) {
            'equipment' => ElectricalEquipment::query()->find($id),
            'material' => InstallationMaterial::query()->find($id),
            default => null,
        };
    }

    /**
     * @param  array{name: string, description?: ?string}  $details
     * @param  array<int|string, array{item: string, line_key?: ?string, adjustment: int|string, tax_percent: float|int|string, discount_percent: float|int|string}>  $lines
     * @param  array{unit_price: float|int|string, tax_percent: float|int|string, discount_percent: float|int|string}  $labor
     */
    public function save(SolarProject $project, SolarProjectSystemDesign $design, array $details, array $lines, array $labor): SolarProjectQuote
    {
        $baseAuto = collect($this->autoLines($project, $design)['lines'])->keyBy('key');
        $scenario = $this->quoteScenario($project, $design, $lines);
        $auto = collect($scenario['auto_lines'])->keyBy('key');
        $prepared = [];

        foreach ($lines as $index => $line) {
            $item = $this->findItem($line['item']);
            $key = $line['line_key'] ?? '';
            $baseLine = $baseAuto->get($key);
            $autoLine = $auto->get($key);
            $isAutoLine = $baseLine !== null && $autoLine !== null
                && $baseLine['item'] === $line['item']
                && $autoLine['item'] === $line['item'];
            $base = $isAutoLine ? $baseLine['quantity'] : 0;
            $quantity = $isAutoLine ? $autoLine['quantity'] : $base + (int) $line['adjustment'];
            $adjustment = $quantity - $base;

            if ($item === null || $quantity < 0) {
                throw ValidationException::withMessages([
                    "lines.$index.adjustment" => 'La cantidad final no puede ser negativa.',
                ]);
            }

            $unitPrice = round((float) $item->price, 2);
            $gross = round($quantity * $unitPrice, 2);
            $tax = round($gross * (float) $line['tax_percent'] / 100, 2);
            $discount = round(($gross + $tax) * (float) $line['discount_percent'] / 100, 2);

            $prepared[] = [
                'item' => $item,
                'concept' => $autoLine !== null && $autoLine['item'] === $line['item'] ? $autoLine['concept'] : null,
                'base_quantity' => $base,
                'adjustment' => $adjustment,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_percent' => (float) $line['tax_percent'],
                'discount_percent' => (float) $line['discount_percent'],
                'gross' => $gross,
                'tax' => $tax,
                'discount' => $discount,
                'total' => round($gross + $tax - $discount, 2),
            ];
        }

        $laborUnit = round((float) $labor['unit_price'], 2);
        $laborTax = round($laborUnit * (float) $labor['tax_percent'] / 100, 2);
        $laborDiscount = round(($laborUnit + $laborTax) * (float) $labor['discount_percent'] / 100, 2);
        $prepared[] = [
            'item' => null,
            'is_labor' => true,
            'name' => 'Mano de obra',
            'concept' => 'Servicio',
            'base_quantity' => 1,
            'adjustment' => 0,
            'quantity' => 1,
            'unit_price' => $laborUnit,
            'tax_percent' => (float) $labor['tax_percent'],
            'discount_percent' => (float) $labor['discount_percent'],
            'gross' => $laborUnit,
            'tax' => $laborTax,
            'discount' => $laborDiscount,
            'total' => round($laborUnit + $laborTax - $laborDiscount, 2),
        ];

        $snapshot = $this->snapshot($project, $scenario['design'], $scenario['layout']);

        return DB::transaction(function () use ($project, $details, $prepared, $snapshot): SolarProjectQuote {
            $collection = collect($prepared);
            $quote = $project->quotes()->create([
                'name' => $details['name'],
                'description' => $details['description'] ?? null,
                'snapshot' => $snapshot,
                'subtotal' => $collection->sum('gross'),
                'tax_total' => $collection->sum('tax'),
                'discount_total' => $collection->sum('discount'),
                'total' => $collection->sum('total'),
            ]);

            foreach ($prepared as $position => $line) {
                $quote->items()->create([
                    'position' => $position + 1,
                    'is_labor' => $line['is_labor'] ?? false,
                    'electrical_equipment_id' => $line['item'] instanceof ElectricalEquipment ? $line['item']->id : null,
                    'installation_material_id' => $line['item'] instanceof InstallationMaterial ? $line['item']->id : null,
                    'name' => $line['name'] ?? $line['item']->name,
                    'concept' => $line['concept'],
                    'base_quantity' => $line['base_quantity'],
                    'adjustment' => $line['adjustment'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_percent' => $line['tax_percent'],
                    'discount_percent' => $line['discount_percent'],
                    'line_total' => $line['total'],
                ]);
            }

            return $quote;
        });
    }

    /**
     * @param  list<array{item: string, line_key?: ?string, adjustment: int|string}>  $lines
     */
    private function adjustmentFor(array $lines, string $key, ?string $item): int
    {
        foreach ($lines as $line) {
            if (($line['line_key'] ?? null) === $key && $item !== null && $line['item'] === $item) {
                return (int) $line['adjustment'];
            }
        }

        return 0;
    }
}
