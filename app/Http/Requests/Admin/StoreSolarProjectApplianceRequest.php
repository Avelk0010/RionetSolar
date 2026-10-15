<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolarProjectApplianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('solarProject');

        return [
            'appliance_id' => [
                'required',
                'integer',
                Rule::exists('appliances', 'id')->where('is_active', true),
                Rule::unique('solar_project_appliances', 'appliance_id')->where(fn (Builder $query) => $query->where('solar_project_id', $project->id)),
            ],
            'quantity_total' => ['required', 'integer', 'min:1'],
            'quantity_night' => ['required', 'integer', 'min:0', 'lte:quantity_total'],
            'usage_hours_total' => ['required', 'numeric', 'min:0', 'max:24'],
            'usage_hours_night' => ['required', 'numeric', 'min:0', 'max:24', 'lte:usage_hours_total'],
        ];
    }

    public function messages(): array
    {
        return [
            'appliance_id.exists' => 'Selecciona un electrodoméstico activo.',
            'appliance_id.unique' => 'Este electrodoméstico ya está agregado al proyecto.',
            'quantity_total.min' => 'La cantidad total debe ser al menos 1.',
            'quantity_night.lte' => 'La cantidad nocturna no puede superar la cantidad total.',
            'usage_hours_total.max' => 'Las horas de uso no pueden superar 24 al día.',
            'usage_hours_night.lte' => 'Las horas nocturnas no pueden superar las horas de uso total.',
        ];
    }

    public function attributes(): array
    {
        return [
            'appliance_id' => 'electrodoméstico',
            'quantity_total' => 'cantidad total',
            'quantity_night' => 'cantidad nocturna',
            'usage_hours_total' => 'horas de uso total',
            'usage_hours_night' => 'horas de uso nocturno',
        ];
    }
}
