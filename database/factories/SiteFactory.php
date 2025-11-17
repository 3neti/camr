<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'division_id' => \App\Models\Division::factory(),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{2}'),
            'last_log_update' => now(),
        ];
    }
}
