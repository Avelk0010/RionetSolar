<?php

namespace App\Http\Requests\Admin;

use App\Services\ElectricalEquipmentCatalog;

class StoreElectricalEquipmentRequest extends ElectricalEquipmentRequest
{
    public function rules(): array
    {
        return array_merge($this->generalRules(), [
            'electrical_equipment_category_id' => ['required', 'integer', 'exists:electrical_equipment_categories,id'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:electrical_equipment,sku'],
        ], ElectricalEquipmentCatalog::rulesFor($this->categorySlug() ?? ''));
    }
}
