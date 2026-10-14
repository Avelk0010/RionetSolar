<?php

namespace App\Http\Requests\Admin;

use App\Services\ElectricalEquipmentCatalog;

class UpdateElectricalEquipmentRequest extends ElectricalEquipmentRequest
{
    public function rules(): array
    {
        $equipment = $this->route('equipment');

        return array_merge($this->generalRules(), [
            'electrical_equipment_category_id' => ['required', 'integer', 'in:'.$equipment->electrical_equipment_category_id],
            'sku' => ['nullable', 'string', 'max:255', 'unique:electrical_equipment,sku,'.$equipment->id],
        ], ElectricalEquipmentCatalog::rulesFor($this->categorySlug() ?? ''));
    }
}
