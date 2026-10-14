<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ElectricalEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'electrical_equipment_category_id', 'name', 'description', 'price', 'brand', 'model', 'sku', 'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ElectricalEquipmentCategory::class, 'electrical_equipment_category_id');
    }

    public function inverterSpec(): HasOne
    {
        return $this->hasOne(InverterSpec::class);
    }

    public function solarPanelSpec(): HasOne
    {
        return $this->hasOne(SolarPanelSpec::class);
    }

    public function batterySpec(): HasOne
    {
        return $this->hasOne(BatterySpec::class);
    }

    public function dcBreakerSpec(): HasOne
    {
        return $this->hasOne(DcBreakerSpec::class);
    }

    public function acBreakerSpec(): HasOne
    {
        return $this->hasOne(AcBreakerSpec::class);
    }

    public function solarCableSpec(): HasOne
    {
        return $this->hasOne(SolarCableSpec::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
