<?php

namespace Database\Seeders;

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
                // Create 2-3 locations per site
                Location::factory()->count(rand(2, 3))->create([
                    'site_id' => $site->id,
                ]);
            }

            $this->command->info('Created locations. Total: '.Location::count());
        } else {
            $this->command->info('Locations already seeded. Total: '.Location::count());
        }
    }
}
