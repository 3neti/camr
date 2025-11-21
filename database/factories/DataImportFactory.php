<?php

namespace Database\Factories;

use App\Models\DataImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataImport>
 */
class DataImportFactory extends Factory
{
    protected $model = DataImport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'filename' => $this->faker->word.'.sql',
            'file_path' => storage_path('app/imports/'.$this->faker->word.'.sql'),
            'status' => $this->faker->randomElement(['uploading', 'queued', 'processing', 'completed', 'failed']),
            'progress' => [
                'current' => $this->faker->numberBetween(0, 100),
                'total' => 100,
            ],
            'statistics' => [
                'sites' => $this->faker->numberBetween(0, 50),
                'users' => $this->faker->numberBetween(0, 20),
                'gateways' => $this->faker->numberBetween(0, 100),
                'meters' => $this->faker->numberBetween(0, 500),
                'meter_data' => $this->faker->numberBetween(0, 10000),
            ],
            'error_message' => null,
        ];
    }

    /**
     * State for completed imports
     */
    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'progress' => ['current' => 100, 'total' => 100],
                'completed_at' => now(),
                'started_at' => now()->subHour(),
            ];
        });
    }

    /**
     * State for failed imports
     */
    public function failed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'error_message' => $this->faker->sentence,
                'completed_at' => now(),
            ];
        });
    }

    /**
     * State for processing imports
     */
    public function processing(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
                'started_at' => now()->subMinutes(5),
            ];
        });
    }
}
