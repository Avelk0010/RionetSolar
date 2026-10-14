<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcBreakerSpec extends Model
{
    protected $fillable = ['electrical_equipment_id', 'operating_voltage_v', 'rated_current_a'];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipment::class, 'electrical_equipment_id');
    }
}
