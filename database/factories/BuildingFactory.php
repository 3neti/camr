<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => \App\Models\Site::factory(),
            'code' => fake()->unique()->bothify('BLD-###'),
            'description' => fake()->words(3, true),
            'billing_cutoff_day' => fake()->numberBetween(1, 28),
        ];
    }
}
