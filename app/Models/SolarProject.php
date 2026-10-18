<?php

namespace App\Models;

use Database\Factories\SolarProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolarProject extends Model
{
    public const DEFAULT_PROJECT_WARRANTY = <<<'WARRANTY'
Garantía del proyecto directamente con la empresa instaladora RIONET ISP SAS

- Paneles Solares: 10 años
- Inversores: 5 años
- Baterías: 5 años

Nota: La garantía no cubre daños por golpes contundentes, equipos visiblemente maltratados y daños por mal manejo o manipulación de equipos instalados por parte de terceros ajenos a RIONET ISP SAS
WARRANTY;

    /** @use HasFactory<SolarProjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'project_warranty', 'solar_hours_per_day', 'latitude', 'longitude'];

    protected $attributes = ['project_warranty' => self::DEFAULT_PROJECT_WARRANTY];

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

    public function stringRows(): HasMany
    {
        return $this->hasMany(SolarProjectStringRow::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(SolarProjectQuote::class)->latest()->latest('id');
    }
}
