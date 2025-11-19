<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Seed in proper order based on dependencies
        $this->call([
            // 1. Core data (creates companies, divisions, sites)
            SiteSeeder::class,
            
            // 2. Users (after sites for site assignments)
            UserSeeder::class,
            
            // 3. Buildings (depends on sites)
            BuildingSeeder::class,
            
            // 4. Configuration files (independent)
            ConfigurationFileSeeder::class,
            
            // 5. Locations (depends on sites and buildings)
            LocationSeeder::class,
            
            // 6. Gateways (depends on sites and locations)
            GatewaySeeder::class,
            
            // 7. Meters (depends on gateways, sites, locations, config files)
            MeterSeeder::class,
            
            // 8. Meter data (depends on meters)
            MeterDataSeeder::class,
            
            // 9. Load profiles (depends on meters with has_load_profile=true)
            LoadProfileSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   User:  test@example.com / password');
    }
}
