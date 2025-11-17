<?php

namespace Database\Seeders;

use App\Models\ConfigurationFile;
use Illuminate\Database\Seeder;

class ConfigurationFileSeeder extends Seeder
{
    public function run(): void
    {
        $configFileCount = ConfigurationFile::count();

        if ($configFileCount < 5) {
            // Create sample configuration files for common meter models
            $meterModels = [
                'Schneider PM5560',
                'ABB M2M',
                'Siemens PAC3200',
                'Landis+Gyr E650',
                'Itron Alpha Plus',
            ];

            foreach ($meterModels as $model) {
                if (!ConfigurationFile::where('meter_model', $model)->exists()) {
                    ConfigurationFile::factory()->create([
                        'meter_model' => $model,
                    ]);
                }
            }

            $this->command->info('Created configuration files. Total: '.ConfigurationFile::count());
        } else {
            $this->command->info('Configuration files already seeded. Total: '.ConfigurationFile::count());
        }
    }
}
