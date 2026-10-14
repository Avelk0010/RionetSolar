<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarCableSpec extends Model
{
    protected $fillable = ['electrical_equipment_id', 'awg_gauge', 'diameter_mm', 'nominal_voltage_dc_v'];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipment::class, 'electrical_equipment_id');
    }
}
