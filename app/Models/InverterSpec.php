<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InverterSpec extends Model
{
    protected $fillable = [
        'electrical_equipment_id', 'mppt_count', 'mppt_max_voltage_v', 'mppt_max_current_a', 'mppt_max_power_w',
        'battery_charge_voltage_v', 'ac_input_voltage_v', 'ac_output_voltage_v', 'max_output_current_a', 'max_output_power_w',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipment::class, 'electrical_equipment_id');
    }
}
