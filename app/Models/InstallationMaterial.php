<?php

namespace App\Models;

use App\Enums\InstallationMaterialCategory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallationMaterial extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'unit', 'category', 'sku', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean', 'category' => InstallationMaterialCategory::class];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
