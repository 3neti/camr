<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Site;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        $sites = Site::all();

        if ($sites->isEmpty()) {
            $this->command->warn('No sites found. Please run SiteSeeder first.');
            return;
        }

$buildingCount = Building::count();

        if ($buildingCount < 15) {
            $buildingNum = 1;
            foreach ($sites as $site) {
                // Create 1-2 buildings per site
                $count = rand(1, 2);
                for ($i = 0; $i < $count; $i++) {
                    Building::create([
                        'site_id' => $site->id,
                        'code' => 'BLDG-' . str_pad($buildingNum++, 3, '0', STR_PAD_LEFT),
                        'description' => 'Building ' . chr(65 + $i), // A, B, C...
                    ]);
                }
            }

            $this->command->info('Created buildings. Total: '.Building::count());
        } else {
            $this->command->info('Buildings already seeded. Total: '.Building::count());
        }
    }
}
