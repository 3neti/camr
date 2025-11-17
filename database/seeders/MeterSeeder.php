<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\Location;
use App\Models\Meter;
use Illuminate\Database\Seeder;

class MeterSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = Gateway::all();
        $locations = Location::all();

        if ($gateways->isEmpty()) {
            $this->command->warn('No gateways found. Please run GatewaySeeder first.');
            return;
        }

        $meterCount = Meter::count();

        if ($meterCount < 40) {
            foreach ($gateways->take(10) as $gateway) {
                // Create 3-5 meters per gateway
                Meter::factory()->count(rand(3, 5))->create([
                    'gateway_id' => $gateway->id,
                    'site_id' => $gateway->site_id,
                    'location_id' => $locations->isNotEmpty() ? $locations->random()->id : null,
                ]);
            }

            $this->command->info('Created meters. Total: '.Meter::count());
        } else {
            $this->command->info('Meters already seeded. Total: '.Meter::count());
        }
    }
}
