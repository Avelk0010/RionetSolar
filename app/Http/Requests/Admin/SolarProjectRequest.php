<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class SolarProjectRequest extends FormRequest
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
            'project_warranty' => ['required', 'string', 'max:10000'],
            'solar_hours_per_day' => ['required', 'numeric', 'gt:0', 'lte:24'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('latitude') !== $this->filled('longitude')) {
                $validator->errors()->add('latitude', 'Ingresa latitud y longitud juntas, o deja ambas vacías.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'solar_hours_per_day.gt' => 'Las horas solares deben ser mayores que cero.',
            'solar_hours_per_day.lte' => 'Las horas solares no pueden superar 24 por día.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90 grados.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180 grados.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'project_warranty' => 'garantía del proyecto',
            'solar_hours_per_day' => 'horas solares al día',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
        ];
    }
}
