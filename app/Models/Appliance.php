<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appliance extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'voltage', 'energy_consumption_wh', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['energy_consumption_wh' => 'decimal:2', 'is_active' => 'boolean'];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
