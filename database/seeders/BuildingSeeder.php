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
            foreach ($sites as $site) {
                // Create 1-2 buildings per site
                Building::factory()->count(rand(1, 2))->create([
                    'site_id' => $site->id,
                ]);
            }

            $this->command->info('Created buildings. Total: '.Building::count());
        } else {
            $this->command->info('Buildings already seeded. Total: '.Building::count());
        }
    }
}
