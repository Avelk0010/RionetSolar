<?php

namespace App\Http\Requests\Admin;

use App\Models\SolarProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSolarProjectStringRowsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array'],
            'rows.*' => ['required', 'array', 'min:1'],
            'rows.*.*' => ['required', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $project = $this->route('solarProject');
            $design = $project instanceof SolarProject ? $project->systemDesign : null;

            if ($design === null || $design->panels_per_string === null) {
                $validator->errors()->add('rows', 'Calcula primero el dimensionamiento del proyecto.');

                return;
            }

            for ($string = 1; $string <= (int) $design->inverter_count; $string++) {
                $rows = $this->parsedRows()[$string] ?? null;

                if ($rows === null) {
                    $validator->errors()->add("rows.$string", sprintf('Define las filas del string %d.', $string));

                    continue;
                }

                if (array_sum($rows) !== (int) $design->panels_per_string) {
                    $validator->errors()->add("rows.$string", sprintf('Las filas del string %d suman %d paneles y deben sumar %d.', $string, array_sum($rows), $design->panels_per_string));
                }
            }
        }];
    }

    /**
     * @return array<int, list<int>>
     */
    public function parsedRows(): array
    {
        $parsed = [];

        foreach ((array) $this->input('rows', []) as $string => $values) {
            if (is_array($values) && $values !== []) {
                $parsed[(int) $string] = array_map('intval', array_values($values));
            }
        }

        return $parsed;
    }

    public function messages(): array
    {
        return [
            'rows.*.array' => 'Define las filas de este string.',
            'rows.*.*.integer' => 'Cada fila debe tener un número entero de paneles.',
            'rows.*.*.min' => 'Cada fila debe tener al menos 1 panel.',
            'rows.*.*.required' => 'Escribe los paneles de cada fila o elimínala.',
        ];
    }
}
