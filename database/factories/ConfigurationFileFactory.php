<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConfigurationFile>
 */
class ConfigurationFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meter_model' => fake()->randomElement(['ION6200', 'PM8000', 'PM5500']),
            'config_file_content' => fake()->text(200),
        ];
    }
}
