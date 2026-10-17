<?php

namespace App\Http\Requests\Admin;

use App\Services\SolarProjectQuoteBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSolarProjectQuoteRequest extends FormRequest
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
            'lines' => ['nullable', 'array'],
            'lines.*.item' => ['required', 'string', 'regex:/^(equipment|material):[1-9]\d*$/'],
            'lines.*.line_key' => ['nullable', 'string', 'max:40'],
            'lines.*.adjustment' => ['required', 'integer', 'between:-100000,100000'],
            'lines.*.tax_percent' => ['required', 'numeric', 'between:0,100'],
            'lines.*.discount_percent' => ['required', 'numeric', 'between:0,100'],
            'labor.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'labor.tax_percent' => ['required', 'numeric', 'between:0,100'],
            'labor.discount_percent' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $builder = app(SolarProjectQuoteBuilder::class);

            foreach ((array) $this->input('lines', []) as $index => $line) {
                if ($builder->findItem($line['item']) === null) {
                    $validator->errors()->add("lines.$index.item", 'El ítem seleccionado ya no existe en el catálogo.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe un nombre para la cotización.',
            'labor.unit_price.required' => 'Indica el valor de la mano de obra (0 si no aplica).',
            'labor.unit_price.min' => 'El valor de la mano de obra no puede ser negativo.',
            'labor.tax_percent.required' => 'Indica el impuesto de la mano de obra.',
            'labor.tax_percent.between' => 'El impuesto debe estar entre 0 y 100%.',
            'labor.discount_percent.required' => 'Indica el descuento de la mano de obra.',
            'labor.discount_percent.between' => 'El descuento debe estar entre 0 y 100%.',
            'lines.*.item.required' => 'Selecciona un ítem en cada fila o elimina la fila vacía.',
            'lines.*.item.regex' => 'Selecciona un ítem válido.',
            'lines.*.adjustment.integer' => 'El ajuste +/- debe ser un número entero.',
            'lines.*.adjustment.required' => 'Indica el ajuste +/- de la fila (0 si no hay).',
            'lines.*.tax_percent.required' => 'Indica el impuesto de la fila.',
            'lines.*.tax_percent.between' => 'El impuesto debe estar entre 0 y 100%.',
            'lines.*.discount_percent.required' => 'Indica el descuento de la fila.',
            'lines.*.discount_percent.between' => 'El descuento debe estar entre 0 y 100%.',
        ];
    }
}
