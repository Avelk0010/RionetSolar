<?php

namespace App\Http\Requests\Admin;

use App\Models\SolarProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveSolarProjectSystemDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'solar_panel_equipment_id' => ['required', 'integer', Rule::exists('electrical_equipment', 'id')->where('is_active', true)],
            'inverter_equipment_id' => ['required', 'integer', Rule::exists('electrical_equipment', 'id')->where('is_active', true)],
            'battery_equipment_id' => ['required', 'integer', Rule::exists('electrical_equipment', 'id')->where('is_active', true)],
            'total_oversizing_percent' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'night_oversizing_percent' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'max_battery_discharge_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $project = $this->route('solarProject');

            if (! $project instanceof SolarProject) {
                return;
            }

            if ((float) $project->total_consumption_wh_per_day <= 0) {
                $validator->errors()->add('solar_panel_equipment_id', 'Agrega electrodomésticos con consumo antes de dimensionar el proyecto.');
            }

            if ((float) $project->maximum_hourly_power_w <= 0) {
                $validator->errors()->add('inverter_equipment_id', 'El proyecto debe tener una potencia pico mayor que cero.');
            }

            if ((float) $project->solar_hours_per_day <= 0) {
                $validator->errors()->add('solar_panel_equipment_id', 'Configura las horas solares del proyecto antes de dimensionar.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'solar_panel_equipment_id.exists' => 'Selecciona un panel solar activo.',
            'inverter_equipment_id.exists' => 'Selecciona un inversor activo.',
            'battery_equipment_id.exists' => 'Selecciona una batería activa.',
            'max_battery_discharge_percent.gt' => 'La descarga máxima debe ser mayor que cero.',
            'max_battery_discharge_percent.lte' => 'La descarga máxima no puede superar 100%.',
        ];
    }

    public function attributes(): array
    {
        return [
            'solar_panel_equipment_id' => 'panel solar',
            'inverter_equipment_id' => 'inversor',
            'battery_equipment_id' => 'batería',
            'total_oversizing_percent' => 'sobredimensionamiento del consumo total (%)',
            'night_oversizing_percent' => 'sobredimensionamiento del consumo nocturno (%)',
            'max_battery_discharge_percent' => 'descarga máxima de batería (%)',
        ];
    }
}
