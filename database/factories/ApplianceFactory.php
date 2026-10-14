<?php

namespace Database\Factories;

use App\Models\Appliance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appliance>
 */
class ApplianceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'voltage' => fake()->randomElement([110, 220]),
            'power_w' => fake()->randomFloat(2, 1, 2500),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
