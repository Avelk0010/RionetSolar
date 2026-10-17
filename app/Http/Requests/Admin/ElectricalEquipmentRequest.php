<?php

namespace App\Http\Requests\Admin;

use App\Models\ElectricalEquipment;
use App\Models\ElectricalEquipmentCategory;
use App\Services\ElectricalEquipmentCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class ElectricalEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function categorySlug(): ?string
    {
        $equipment = $this->route('equipment');
        $categoryId = $equipment instanceof ElectricalEquipment
            ? $equipment->electrical_equipment_category_id
            : $this->input('electrical_equipment_category_id');

        return ElectricalEquipmentCategory::query()->whereKey($categoryId)->value('slug');
    }

    protected function generalRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $slug = $this->categorySlug();

            if ($slug === null || ! ElectricalEquipmentCatalog::supports($slug)) {
                $validator->errors()->add('electrical_equipment_category_id', 'Selecciona una categoría de equipo válida.');

                return;
            }

            $selectedFields = ElectricalEquipmentCatalog::specifications()[$slug]['fields'];

            foreach (ElectricalEquipmentCatalog::specifications() as $otherSlug => $specification) {
                if ($otherSlug === $slug) {
                    continue;
                }

                foreach ($specification['fields'] as $field) {
                    if (in_array($field, $selectedFields, true)) {
                        continue;
                    }

                    if ($this->input($field) !== null && $this->input($field) !== '') {
                        $validator->errors()->add($field, 'Este dato técnico no corresponde a la categoría seleccionada.');
                    }
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute debe ser mayor o igual a :min.',
            'gt' => 'El campo :attribute debe ser mayor que cero.',
            'gte' => 'El campo :attribute debe ser mayor o igual que el mínimo.',
            'in' => 'El valor seleccionado para :attribute no es válido.',
            'regex' => 'El calibre AWG debe tener un formato válido, por ejemplo 4/0 o 10.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'price' => 'precio (COP)',
            'electrical_equipment_category_id' => 'categoría',
            'mppt_count' => 'cantidad de MPPT',
            'mppt_max_voltage_v' => 'voltaje máximo MPPT (V)',
            'mppt_max_current_a' => 'corriente máxima MPPT (A)',
            'mppt_max_power_w' => 'potencia máxima MPPT (W)',
            'battery_charge_voltage_v' => 'voltaje de carga de batería (V)',
            'ac_input_voltage_v' => 'voltaje de entrada AC (V)',
            'ac_output_voltage_v' => 'voltaje de salida AC (V)',
            'max_output_current_a' => 'corriente máxima de salida (A)',
            'max_output_power_w' => 'potencia máxima de salida (W)',
            'max_power_w' => 'potencia máxima (W)',
            'max_power_voltage_v' => 'voltaje de máxima potencia Vmp (V)',
            'max_power_current_a' => 'corriente de máxima potencia Imp (A)',
            'open_circuit_voltage_v' => 'voltaje de circuito abierto (V)',
            'short_circuit_current_a' => 'corriente de cortocircuito (A)',
            'max_system_voltage_v' => 'voltaje máximo del sistema (V)',
            'length_mm' => 'largo (mm)',
            'width_mm' => 'ancho (mm)',
            'height_mm' => 'alto (mm)',
            'nominal_power_w' => 'potencia nominal (W)',
            'capacity_ah' => 'capacidad (Ah)',
            'voltage_v' => 'voltaje nominal (V)',
            'min_operating_voltage_v' => 'voltaje mínimo de operación (V)',
            'max_operating_voltage_v' => 'voltaje máximo de operación (V)',
            'operating_voltage_v' => 'voltaje de operación (V)',
            'poles' => 'cantidad de polos',
            'rated_current_a' => 'corriente nominal (A)',
            'awg_gauge' => 'calibre AWG',
            'diameter_mm' => 'diámetro (mm)',
            'nominal_voltage_dc_v' => 'voltaje nominal DC (V)',
        ];
    }
}
