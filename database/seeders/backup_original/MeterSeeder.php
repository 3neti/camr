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
            $meterNum = $meterCount + 1;
            $meterTypes = ['Electric', 'Water', 'Gas', 'Steam'];
            $brands = ['Schneider', 'ABB', 'Siemens', 'Landis+Gyr', 'Itron'];
            
            foreach ($gateways->take(10) as $gateway) {
                // Create 3-5 meters per gateway
                $count = rand(3, 5);
                for ($i = 0; $i < $count; $i++) {
                    Meter::create([
                        'gateway_id' => $gateway->id,
                        'site_id' => $gateway->site_id,
                        'location_id' => $locations->isNotEmpty() ? $locations->random()->id : null,
                        'name' => 'MTR-' . str_pad($meterNum, 4, '0', STR_PAD_LEFT),
                        'type' => $meterTypes[array_rand($meterTypes)],
                        'brand' => $brands[array_rand($brands)],
                        'customer_name' => 'Customer ' . chr(65 + ($meterNum % 26)),
                        'has_load_profile' => rand(0, 1) === 1,
                        'status' => 'Active',
                    ]);
                    $meterNum++;
                }
            }

            $this->command->info('Created meters. Total: '.Meter::count());
        } else {
            $this->command->info('Meters already seeded. Total: '.Meter::count());
        }
        
        // Update existing meters to enable load profiles for some
        Meter::whereNull('has_load_profile')
            ->orWhere('has_load_profile', false)
            ->inRandomOrder()
            ->limit(20)
            ->update(['has_load_profile' => true]);
            
        $this->command->info('Meters with load profiles: '.Meter::where('has_load_profile', true)->count());
    }
}
