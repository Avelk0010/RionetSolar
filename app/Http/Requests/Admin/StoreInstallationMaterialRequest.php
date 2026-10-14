<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstallationMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:40'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:installation_materials,sku'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'price.min' => 'El precio no puede ser negativo.',
            'sku.unique' => 'Ya existe un material con este SKU.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'price' => 'precio (COP)', 'unit' => 'unidad', 'sku' => 'SKU'];
    }
}
