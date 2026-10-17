<?php

namespace App\Http\Requests\Admin;

use App\Enums\InstallationMaterialCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstallationMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = InstallationMaterialCategory::tryFrom((string) $this->input('category'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:40'],
            'category' => array_values(array_filter(['required', Rule::enum(InstallationMaterialCategory::class), $category?->isSingleEntry() ? Rule::unique('installation_materials', 'category') : null])),
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
            'category.unique' => 'Ya hay un material registrado en esta categoría; solo se permite uno. Edítalo o elige otra categoría.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'price' => 'precio (COP)', 'unit' => 'unidad', 'sku' => 'SKU', 'category' => 'categoría'];
    }
}
