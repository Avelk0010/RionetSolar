<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPanelSpec extends Model
{
    protected $fillable = [
        'electrical_equipment_id', 'max_power_w', 'open_circuit_voltage_v', 'short_circuit_current_a',
        'max_system_voltage_v', 'length_mm', 'width_mm', 'height_mm',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipment::class, 'electrical_equipment_id');
    }
}
