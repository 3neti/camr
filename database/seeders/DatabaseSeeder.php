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

        // Import LIVE data from legacy SQL dump
        $this->call([
            // Import all real data from SQL dump
            // This includes: sites, users, gateways, meters, and meter readings
            LiveDataSeeder::class,
            
            // Import fresh meter readings from CSV files
            // These contain the most recent data exported from the live system
            CsvMeterDataSeeder::class,
            
            // Optional: Configuration files (if not in SQL dump)
            ConfigurationFileSeeder::class,
        ]);
        
        $this->command->newLine();
        $this->command->comment('💡 All data imported from production SQL dump!');
        $this->command->comment('💡 Fake seeders backed up in: database/seeders/backup_original/');

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   Users imported from SQL dump');
        $this->command->info('   Default password for all users: password');
    }
}
