<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Location;
use App\Models\Site;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $sites = Site::all();

        if ($sites->isEmpty()) {
            $this->command->warn('No sites found. Please run SiteSeeder first.');
            return;
        }

$locationCount = Location::count();

        if ($locationCount < 20) {
            foreach ($sites as $site) {
                // Get buildings for this site
                $siteBuildings = Building::where('site_id', $site->id)->get();
                
                // Create 2-3 locations per site
                $count = rand(2, 3);
                for ($i = 0; $i < $count; $i++) {
                    Location::factory()->create([
                        'site_id' => $site->id,
                        // ~50% chance to link with a building
                        'building_id' => $siteBuildings->isNotEmpty() && rand(0, 1) === 1 
                            ? $siteBuildings->random()->id 
                            : null,
                    ]);
                }
            }

            $this->command->info('Created locations. Total: '.Location::count());
        } else {
            $this->command->info('Locations already seeded. Total: '.Location::count());
        }
    }
}
