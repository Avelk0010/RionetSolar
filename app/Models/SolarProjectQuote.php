<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolarProjectQuote extends Model
{
    private const FOLIO_OFFSET = 30;

    protected $fillable = ['solar_project_id', 'name', 'description', 'snapshot', 'subtotal', 'tax_total', 'discount_total', 'total'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'subtotal' => 'decimal:2', 'tax_total' => 'decimal:2', 'discount_total' => 'decimal:2', 'total' => 'decimal:2'];
    }

    protected function folio(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->getKey() + self::FOLIO_OFFSET);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(SolarProject::class, 'solar_project_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SolarProjectQuoteItem::class)->orderBy('position');
    }
}
