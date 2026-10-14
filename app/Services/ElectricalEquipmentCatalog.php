<?php

namespace App\Services;

use App\Models\AcBreakerSpec;
use App\Models\BatterySpec;
use App\Models\DcBreakerSpec;
use App\Models\ElectricalEquipment;
use App\Models\ElectricalEquipmentCategory;
use App\Models\InverterSpec;
use App\Models\SolarCableSpec;
use App\Models\SolarPanelSpec;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectricalEquipmentCatalog
{
    private const SPECIFICATIONS = [
        'inversores' => [
            'relation' => 'inverterSpec',
            'fields' => ['mppt_count', 'mppt_max_voltage_v', 'mppt_max_current_a', 'mppt_max_power_w', 'battery_charge_voltage_v', 'ac_input_voltage_v', 'ac_output_voltage_v', 'max_output_current_a', 'max_output_power_w'],
            'rules' => [
                'mppt_count' => ['required', 'integer', 'min:1'],
                'mppt_max_voltage_v' => ['required', 'numeric', 'gt:0'],
                'mppt_max_current_a' => ['required', 'numeric', 'gt:0'],
                'mppt_max_power_w' => ['required', 'numeric', 'gt:0'],
                'battery_charge_voltage_v' => ['required', 'numeric', 'gt:0'],
                'ac_input_voltage_v' => ['required', 'integer', 'in:110,220'],
                'ac_output_voltage_v' => ['required', 'integer', 'in:110,220'],
                'max_output_current_a' => ['required', 'numeric', 'gt:0'],
                'max_output_power_w' => ['required', 'numeric', 'gt:0'],
            ],
            'model' => InverterSpec::class,
        ],
        'paneles-solares' => [
            'relation' => 'solarPanelSpec',
            'fields' => ['max_power_w', 'open_circuit_voltage_v', 'short_circuit_current_a', 'max_system_voltage_v', 'length_mm', 'width_mm', 'height_mm'],
            'rules' => [
                'max_power_w' => ['required', 'numeric', 'gt:0'],
                'open_circuit_voltage_v' => ['required', 'numeric', 'gt:0'],
                'short_circuit_current_a' => ['required', 'numeric', 'gt:0'],
                'max_system_voltage_v' => ['required', 'numeric', 'gt:0'],
                'length_mm' => ['required', 'numeric', 'gt:0'],
                'width_mm' => ['required', 'numeric', 'gt:0'],
                'height_mm' => ['required', 'numeric', 'gt:0'],
            ],
            'model' => SolarPanelSpec::class,
        ],
        'baterias' => [
            'relation' => 'batterySpec',
            'fields' => ['nominal_power_w', 'capacity_ah', 'voltage_v'],
            'rules' => [
                'nominal_power_w' => ['nullable', 'numeric', 'gt:0'],
                'capacity_ah' => ['required', 'numeric', 'gt:0'],
                'voltage_v' => ['required', 'numeric', 'gt:0'],
            ],
            'model' => BatterySpec::class,
        ],
        'breakers-dc' => [
            'relation' => 'dcBreakerSpec',
            'fields' => ['min_operating_voltage_v', 'max_operating_voltage_v', 'rated_current_a'],
            'rules' => [
                'min_operating_voltage_v' => ['required', 'numeric', 'gt:0'],
                'max_operating_voltage_v' => ['required', 'numeric', 'gt:0', 'gte:min_operating_voltage_v'],
                'rated_current_a' => ['required', 'numeric', 'gt:0'],
            ],
            'model' => DcBreakerSpec::class,
        ],
        'breakers-ac' => [
            'relation' => 'acBreakerSpec',
            'fields' => ['operating_voltage_v', 'rated_current_a'],
            'rules' => [
                'operating_voltage_v' => ['required', 'integer', 'in:110,220'],
                'rated_current_a' => ['required', 'numeric', 'gt:0'],
            ],
            'model' => AcBreakerSpec::class,
        ],
        'cable-solar' => [
            'relation' => 'solarCableSpec',
            'fields' => ['awg_gauge', 'diameter_mm', 'nominal_voltage_dc_v'],
            'rules' => [
                'awg_gauge' => ['required', 'string', 'max:8', 'regex:/^(?:[0-9]{1,3}|[1-9]\/0)$/'],
                'diameter_mm' => ['required', 'numeric', 'gt:0'],
                'nominal_voltage_dc_v' => ['required', 'numeric', 'gt:0'],
            ],
            'model' => SolarCableSpec::class,
        ],
    ];

    public static function specifications(): array
    {
        return self::SPECIFICATIONS;
    }

    public static function rulesFor(string $slug): array
    {
        return self::SPECIFICATIONS[$slug]['rules'] ?? [];
    }

    public static function supports(string $slug): bool
    {
        return array_key_exists($slug, self::SPECIFICATIONS);
    }

    public function create(array $data): ElectricalEquipment
    {
        return DB::transaction(function () use ($data): ElectricalEquipment {
            $category = ElectricalEquipmentCategory::query()->findOrFail($data['electrical_equipment_category_id']);
            $specification = $this->specificationFor($category->slug);
            $equipment = ElectricalEquipment::query()->create(Arr::only($data, [
                'electrical_equipment_category_id', 'name', 'description', 'price', 'brand', 'model', 'sku', 'is_active',
            ]));

            $equipment->{$specification['relation']}()->create(Arr::only($data, $specification['fields']));

            return $equipment;
        });
    }

    public function update(ElectricalEquipment $equipment, array $data): ElectricalEquipment
    {
        return DB::transaction(function () use ($equipment, $data): ElectricalEquipment {
            $equipment->refresh();
            $specification = $this->specificationFor($equipment->category->slug);
            $equipment->update(Arr::only($data, ['name', 'description', 'price', 'brand', 'model', 'sku', 'is_active']));
            $equipment->{$specification['relation']}()->updateOrCreate(
                [],
                Arr::only($data, $specification['fields']),
            );

            return $equipment;
        });
    }

    private function specificationFor(string $slug): array
    {
        if (! self::supports($slug)) {
            throw ValidationException::withMessages([
                'electrical_equipment_category_id' => 'La categoría seleccionada no tiene un formulario técnico disponible.',
            ]);
        }

        return self::SPECIFICATIONS[$slug];
    }
}
