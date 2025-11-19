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
            $gatewayNum = $gatewayCount + 1;
            foreach ($sites->take(5) as $site) {
                // Create 3-4 gateways per site
                $count = rand(3, 4);
                for ($i = 0; $i < $count; $i++) {
                    Gateway::create([
                        'site_id' => $site->id,
                        'location_id' => $locations->isNotEmpty() ? $locations->random()->id : null,
                        'serial_number' => 'GW-' . str_pad($gatewayNum, 6, '0', STR_PAD_LEFT),
                        'mac_address' => sprintf('%02X:%02X:%02X:%02X:%02X:%02X', 
                            rand(0, 255), rand(0, 255), rand(0, 255), 
                            rand(0, 255), rand(0, 255), rand(0, 255)),
                        'ip_address' => '192.168.' . rand(1, 254) . '.' . rand(1, 254),
                        'connection_type' => 'LAN',
                    ]);
                    $gatewayNum++;
                }
            }

            $this->command->info("Created {$toCreate} gateways. Total: ".Gateway::count());
        } else {
            $this->command->info('Gateways already seeded. Total: '.Gateway::count());
        }
    }
}
