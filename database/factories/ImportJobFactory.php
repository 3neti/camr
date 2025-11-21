<?php

namespace Database\Factories;

use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportJobFactory extends Factory
{
    protected $model = ImportJob::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['sql_dump', 'csv_import']),
            'filename' => $this->faker->word() . '.sql',
            'total_records' => $this->faker->numberBetween(100, 10000),
            'processed_records' => $this->faker->numberBetween(0, 10000),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed', 'cancelled']),
            'options' => [],
            'result' => null,
            'error' => null,
            'user_id' => User::factory(),
            'started_at' => $this->faker->optional()->dateTime(),
            'completed_at' => $this->faker->optional()->dateTime(),
        ];
    }

    public function pending(): self
    {
        return $this->state(['status' => 'pending']);
    }

    public function processing(): self
    {
        return $this->state(['status' => 'processing']);
    }

    public function completed(): self
    {
        return $this->state(['status' => 'completed', 'completed_at' => now()]);
    }

    public function failed(): self
    {
        return $this->state(['status' => 'failed', 'error' => 'Import failed']);
    }

    public function cancelled(): self
    {
        return $this->state(['status' => 'cancelled']);
    }
}
