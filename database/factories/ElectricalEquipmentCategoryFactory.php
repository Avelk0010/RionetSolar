<?php

namespace Database\Factories;

use App\Models\ElectricalEquipmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectricalEquipmentCategory>
 */
class ElectricalEquipmentCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
