<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarProjectStringRow extends Model
{
    protected $fillable = ['solar_project_id', 'string_number', 'row_number', 'panel_count'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(SolarProject::class, 'solar_project_id');
    }
}
