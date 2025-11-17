<?php

namespace Database\Seeders;

use App\Models\Meter;
use App\Models\LoadProfile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoadProfileSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get meters that have load profiles enabled
        $meters = Meter::where('has_load_profile', true)->get();
        
        // Generate 15-minute interval data for the past 7 days
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(7);
        
        foreach ($meters as $meter) {
            $currentDate = $startDate->copy();
            
            // Base power values (kW)
            $baseDeliveredPower = 10; // 10kW base delivered
            $baseReceivedPower = 1;   // 1kW base received
            
            $deliveredEnergy = 0; // Cumulative kWh
            $receivedEnergy = 0;  // Cumulative kWh
            
            while ($currentDate <= $endDate) {
                $hour = $currentDate->hour;
                $loadFactor = $this->getLoadFactor($hour);
                $randomness = 1 + (rand(-15, 15) / 100);
                
                // Power readings (kW)
                $deliveredPower = $baseDeliveredPower * $loadFactor * $randomness;
                $receivedPower = $baseReceivedPower * $loadFactor * $randomness * 0.1; // Less generation
                
                // Energy in 15 minutes (kWh) = Power * 0.25 hours
                $deliveredInterval = $deliveredPower * 0.25;
                $receivedInterval = $receivedPower * 0.25;
                
                // Accumulate
                $deliveredEnergy += $deliveredInterval;
                $receivedEnergy += $receivedInterval;
                
                // Reactive power (kvar) - typically 30% of real power
                $deliveredReactive = $deliveredInterval * 0.3;
                $receivedReactive = $receivedInterval * 0.3;
                
                LoadProfile::create([
                    'meter_name' => $meter->name,
                    'reading_datetime' => $currentDate,
                    'event_id' => sprintf('LP-%s-%s', $meter->id, $currentDate->format('YmdHis')),
                    
                    // Load profile channels
                    'channel_1' => $deliveredPower,      // 1.5.0 kW (delivered power)
                    'channel_2' => $deliveredEnergy,     // 1-1:1.30.2 kWh (delivered energy)
                    'channel_3' => $deliveredReactive,   // 1-1:3.30.2 kvarh (delivered reactive)
                    'channel_4' => $receivedPower,       // 2.5.0 kW (received power)
                    'channel_5' => $receivedEnergy,      // 1-1:2.30.2 kWh (received energy)
                    'channel_6' => $receivedReactive,    // 1-1:4.30.2 kvarh (received reactive)
                    'channel_7' => $deliveredPower * 1.1, // VA
                    'channel_8' => $deliveredPower * 0.9, // Power factor related
                ]);
                
                $currentDate->addMinutes(15);
            }
        }
    }
    
    /**
     * Get load factor based on hour of day (0-1)
     */
    private function getLoadFactor(int $hour): float
    {
        // Simulate typical commercial building load curve
        if ($hour >= 0 && $hour < 6) {
            return 0.3; // Night: low load
        } elseif ($hour >= 6 && $hour < 9) {
            return 0.6; // Morning: ramping up
        } elseif ($hour >= 9 && $hour < 17) {
            return 0.9; // Business hours: high load
        } elseif ($hour >= 17 && $hour < 20) {
            return 0.7; // Evening: medium load
        } else {
            return 0.4; // Late evening: low-medium load
        }
    }
}
