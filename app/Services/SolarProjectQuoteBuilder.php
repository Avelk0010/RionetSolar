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
    ) {}

    /**
     * Líneas que salen del dimensionamiento, con el ítem del catálogo ya seleccionado.
     *
     * @return array{lines: list<array{key: string, concept: string, item: string, name: string, quantity: int, unit_price: float}>, missing: list<string>}
     */
    public function autoLines(SolarProject $project, SolarProjectSystemDesign $design): array
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

        $quantities = $this->materials->calculate($this->materials->layout($project, $design));
        $catalog = $this->materials->catalogItems();

        foreach (self::MATERIAL_LABELS as $key => $label) {
            $add($key, $label, $catalog[$key], $quantities[$key]);
        }

        return ['lines' => $lines, 'missing' => $missing];
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
        $auto = collect($this->autoLines($project, $design)['lines'])->keyBy('key');
        $prepared = [];

        foreach ($lines as $index => $line) {
            $item = $this->findItem($line['item']);
            $autoLine = $auto->get($line['line_key'] ?? '');
            $base = $autoLine !== null && $autoLine['item'] === $line['item'] ? $autoLine['quantity'] : 0;
            $quantity = $base + (int) $line['adjustment'];

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
                'adjustment' => (int) $line['adjustment'],
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

        return DB::transaction(function () use ($project, $details, $prepared): SolarProjectQuote {
            $collection = collect($prepared);
            $quote = $project->quotes()->create([
                'name' => $details['name'],
                'description' => $details['description'] ?? null,
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
}
