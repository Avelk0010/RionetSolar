<?php

namespace Database\Factories;

use App\Models\InstallationMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstallationMaterial>
 */
class InstallationMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 0, 1000000),
            'unit' => 'unidad',
            'category' => 'otro',
            'sku' => null,
            'is_active' => true,
        ];
    }
}
