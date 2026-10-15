<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarProjectSystemDesign extends Model
{
    protected $fillable = [
        'solar_project_id', 'solar_panel_equipment_id', 'inverter_equipment_id', 'battery_equipment_id',
        'total_oversizing_percent', 'night_oversizing_percent', 'max_battery_discharge_percent',
        'panel_count', 'inverter_count', 'battery_count', 'panels_per_string', 'array_power_w',
    ];

    protected function casts(): array
    {
        return [
            'total_oversizing_percent' => 'decimal:2',
            'night_oversizing_percent' => 'decimal:2',
            'max_battery_discharge_percent' => 'decimal:2',
            'array_power_w' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(SolarProject::class, 'solar_project_id');
    }

    public function solarPanel(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipment::class, 'solar_panel_equipment_id');
    }

    public function inverter(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipment::class, 'inverter_equipment_id');
    }

    public function battery(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipment::class, 'battery_equipment_id');
    }
}
