<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('appliances', 'name')],
            'voltage' => ['required', 'integer', 'in:110,220'],
            'energy_consumption_wh' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'voltage.in' => 'El voltaje debe ser 110 V o 220 V.',
            'energy_consumption_wh.gt' => 'El consumo energético en una hora debe ser mayor que cero.',
            'name.unique' => 'Ya existe un electrodoméstico con este nombre.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'voltage' => 'voltaje', 'energy_consumption_wh' => 'consumo en una hora (Wh)'];
    }
}
