<?php

namespace Database\Seeders;

use App\Models\Meter;
use App\Models\MeterData;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MeterDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get all meters
        $meters = Meter::with('location')->get();
        
        // Generate hourly data for the past 30 days
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);
        
        foreach ($meters as $meter) {
            $currentDate = $startDate->copy();
            
            // Base values that will vary throughout the day
            $baseVoltage = 230; // 230V nominal
            $baseCurrent = 50; // 50A nominal
            $basePower = 10000; // 10kW base load
            
            $totalWhDelivered = 0;
            $totalWhReceived = 0;
            
            while ($currentDate <= $endDate) {
                $hour = $currentDate->hour;
                
                // Simulate daily load curve (higher during business hours)
                $loadFactor = $this->getLoadFactor($hour);
                
                // Add some randomness
                $randomness = 1 + (rand(-10, 10) / 100);
                
                // Calculate values
                $voltage = $baseVoltage * (0.95 + rand(0, 10) / 100); // ±5%
                $current = $baseCurrent * $loadFactor * $randomness;
                $watt = $basePower * $loadFactor * $randomness;
                $powerFactor = 0.85 + (rand(0, 10) / 100);
                $va = $watt / $powerFactor;
                $var = sqrt($va * $va - $watt * $watt);
                
                // Accumulate energy
                $totalWhDelivered += $watt; // 1 hour of consumption
                $totalWhReceived += $watt * 0.02; // Small amount of generation
                
                MeterData::create([
                    'location' => $meter->location->description ?? 'Home',
                    'meter_name' => $meter->name,
                    'reading_datetime' => $currentDate,
                    
                    // Voltage (3-phase)
                    'vrms_a' => $voltage,
                    'vrms_b' => $voltage * 0.98,
                    'vrms_c' => $voltage * 1.02,
                    
                    // Current (3-phase)
                    'irms_a' => $current,
                    'irms_b' => $current * 0.95,
                    'irms_c' => $current * 1.05,
                    
                    // Power measurements
                    'frequency' => 60.0 + (rand(-5, 5) / 100),
                    'power_factor' => $powerFactor,
                    'watt' => $watt,
                    'va' => $va,
                    'var' => $var,
                    
                    // Energy measurements
                    'wh_delivered' => $totalWhDelivered,
                    'wh_received' => $totalWhReceived,
                    'wh_net' => $totalWhDelivered - $totalWhReceived,
                    'wh_total' => $totalWhDelivered + $totalWhReceived,
                    
                    // Reactive energy
                    'varh_positive' => $totalWhDelivered * 0.3,
                    'varh_negative' => $totalWhReceived * 0.3,
                    'varh_net' => $totalWhDelivered * 0.3 - $totalWhReceived * 0.3,
                    'varh_total' => $totalWhDelivered * 0.3 + $totalWhReceived * 0.3,
                    
                    // Apparent energy
                    'vah_total' => $totalWhDelivered / $powerFactor,
                    
                    // Demand measurements
                    'max_del_kw_demand' => $watt / 1000,
                    'max_del_kw_demand_time' => $currentDate,
                    
                    // Phase angles
                    'v_phase_angle_a' => 0,
                    'v_phase_angle_b' => -120,
                    'v_phase_angle_c' => 120,
                    'i_phase_angle_a' => -30,
                    'i_phase_angle_b' => -150,
                    'i_phase_angle_c' => 90,
                    
                    // Metadata
                    'mac_address' => $meter->gateway->mac_address ?? null,
                    'software_version' => $meter->gateway->software_version ?? '1.0.0',
                    'relay_status' => true,
                    'genset_status' => false,
                ]);
                
                $currentDate->addHour();
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
