<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gateway>
 */
class GatewayFactory extends Factory
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
            'location_id' => \App\Models\Location::factory(),
            'site_code' => $this->faker->regexify('[A-Z]{3}-[0-9]{2}'),
            'serial_number' => $this->faker->unique()->bothify('GW-####-####'),
            'mac_address' => $this->faker->unique()->macAddress(),
            'ip_address' => $this->faker->unique()->localIpv4(),
            'connection_type' => $this->faker->randomElement(['LAN', '3G', '4G', '5G']),
            'last_log_update' => now(),
        ];
    }
}
