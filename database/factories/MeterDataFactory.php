<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeterData>
 */
class MeterDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location' => 'SMSS',
            'meter_name' => '030011100592',
            'reading_datetime' => now(),
            'vrms_a' => fake()->randomFloat(1, 200, 240),
            'vrms_b' => fake()->randomFloat(1, 200, 240),
            'vrms_c' => fake()->randomFloat(1, 200, 240),
            'irms_a' => fake()->randomFloat(1, 0, 50),
            'irms_b' => fake()->randomFloat(1, 0, 50),
            'irms_c' => fake()->randomFloat(1, 0, 50),
            'frequency' => 60.0,
            'power_factor' => fake()->randomFloat(2, 0.8, 1.0),
            'watt' => fake()->randomFloat(2, 0, 100),
            'va' => fake()->randomFloat(2, 0, 150),
            'var' => fake()->randomFloat(2, 0, 50),
            'wh_delivered' => fake()->randomFloat(2, 0, 1000),
            'wh_received' => 0,
            'wh_net' => 0,
            'wh_total' => fake()->randomFloat(4, 0, 100000),
            'varh_negative' => 0,
            'varh_positive' => 0,
            'varh_net' => 0,
            'varh_total' => fake()->randomFloat(0, 0, 50000),
            'vah_total' => 0,
            'max_rec_kw_demand' => 0,
            'max_rec_kw_demand_time' => null,
            'max_del_kw_demand' => 0,
            'max_del_kw_demand_time' => null,
            'max_pos_kvar_demand' => 0,
            'max_pos_kvar_demand_time' => null,
            'max_neg_kvar_demand' => 0,
            'max_neg_kvar_demand_time' => null,
            'v_phase_angle_a' => 0,
            'v_phase_angle_b' => 0,
            'v_phase_angle_c' => 0,
            'i_phase_angle_a' => 0,
            'i_phase_angle_b' => 0,
            'i_phase_angle_c' => 0,
            'mac_address' => null,
            'software_version' => '2.10',
            'relay_status' => false,
            'genset_status' => false,
        ];
    }
}
