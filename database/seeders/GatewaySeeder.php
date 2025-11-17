<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\Location;
use App\Models\Site;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        // Get existing sites and locations
        $sites = Site::all();
        $locations = Location::all();

        if ($sites->isEmpty()) {
            $this->command->warn('No sites found. Please run SiteSeeder first.');
            return;
        }

        // Create 15-20 gateways distributed across sites
        $gatewayCount = Gateway::count();
        $toCreate = max(0, 15 - $gatewayCount);

        if ($toCreate > 0) {
            foreach ($sites->take(5) as $site) {
                // Create 3-4 gateways per site
                Gateway::factory()->count(rand(3, 4))->create([
                    'site_id' => $site->id,
                    'location_id' => $locations->isNotEmpty() ? $locations->random()->id : null,
                ]);
            }

            $this->command->info("Created {$toCreate} gateways. Total: ".Gateway::count());
        } else {
            $this->command->info('Gateways already seeded. Total: '.Gateway::count());
        }
    }
}
