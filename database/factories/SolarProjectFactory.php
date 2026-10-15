<?php

namespace Database\Factories;

use App\Models\SolarProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolarProject>
 */
class SolarProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'solar_hours_per_day' => fake()->randomFloat(2, 3, 8),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
