<?php

namespace App\Models;

use Database\Factories\SolarProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolarProject extends Model
{
    /** @use HasFactory<SolarProjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'solar_hours_per_day', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'solar_hours_per_day' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'total_consumption_wh_per_day' => 'decimal:2',
            'night_consumption_wh_per_day' => 'decimal:2',
            'maximum_hourly_power_w' => 'decimal:2',
        ];
    }

    public function applianceItems(): HasMany
    {
        return $this->hasMany(SolarProjectAppliance::class);
    }

    public function systemDesign(): HasOne
    {
        return $this->hasOne(SolarProjectSystemDesign::class);
    }
}
