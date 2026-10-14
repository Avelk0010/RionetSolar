<?php

namespace Database\Factories;

use App\Models\ElectricalEquipment;
use App\Models\ElectricalEquipmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectricalEquipment>
 */
class ElectricalEquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'electrical_equipment_category_id' => ElectricalEquipmentCategory::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 1000, 10000000),
            'brand' => fake()->optional()->company(),
            'model' => fake()->optional()->bothify('REF-####'),
            'sku' => null,
            'is_active' => true,
        ];
    }
}
