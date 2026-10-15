<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarProjectAppliance extends Model
{
    protected $fillable = [
        'solar_project_id', 'appliance_id', 'appliance_energy_consumption_wh', 'quantity_total', 'quantity_night',
        'usage_hours_total', 'usage_hours_night', 'daily_energy_wh', 'night_energy_wh', 'peak_power_w',
    ];

    protected function casts(): array
    {
        return [
            'appliance_energy_consumption_wh' => 'decimal:2',
            'usage_hours_total' => 'decimal:2',
            'usage_hours_night' => 'decimal:2',
            'daily_energy_wh' => 'decimal:2',
            'night_energy_wh' => 'decimal:2',
            'peak_power_w' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(SolarProject::class, 'solar_project_id');
    }

    public function appliance(): BelongsTo
    {
        return $this->belongsTo(Appliance::class);
    }
}
