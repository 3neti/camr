<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meter>
 */
class MeterFactory extends Factory
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
            'gateway_id' => \App\Models\Gateway::factory(),
            'location_id' => \App\Models\Location::factory(),
            'building_id' => \App\Models\Building::factory(),
            'configuration_file_id' => \App\Models\ConfigurationFile::factory(),
            'site_code' => $this->faker->regexify('[A-Z]{3}-[0-9]{2}'),
            'name' => $this->faker->unique()->bothify('MTR-####'),
            'default_name' => $this->faker->bothify('MTR-####'),
            'type' => $this->faker->randomElement(['ION6200', 'PM8000', 'PM5500']),
            'brand' => $this->faker->randomElement(['Schneider', 'ABB', 'Siemens']),
            'role' => $this->faker->randomElement(['Main', 'Sub', 'Check', 'Client Meter']),
            'customer_name' => $this->faker->company(),
            'multiplier' => $this->faker->randomElement([1, 10, 100, 1000]),
            'status' => 'Active',
            'last_log_update' => now(),
        ];
    }
}
