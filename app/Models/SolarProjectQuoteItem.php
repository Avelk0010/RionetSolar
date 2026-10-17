<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarProjectQuoteItem extends Model
{
    protected $fillable = [
        'solar_project_quote_id', 'position', 'is_labor', 'electrical_equipment_id', 'installation_material_id', 'name', 'concept',
        'base_quantity', 'adjustment', 'quantity', 'unit_price', 'tax_percent', 'discount_percent', 'line_total',
    ];

    protected function casts(): array
    {
        return ['is_labor' => 'boolean', 'unit_price' => 'decimal:2', 'tax_percent' => 'decimal:2', 'discount_percent' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(SolarProjectQuote::class, 'solar_project_quote_id');
    }
}
