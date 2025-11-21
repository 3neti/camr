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

        // Seed users only
        $this->call(UserSeeder::class);
        
        // TODO: Restore these seeders for demo data in the future
        // $this->call([
        //     // Import all real data from SQL dump
        //     // This includes: sites, users, gateways, meters, and meter readings
        //     LiveDataSeeder::class,
        //     
        //     // Import fresh meter readings from CSV files
        //     // These contain the most recent data exported from the live system
        //     CsvMeterDataSeeder::class,
        //     
        //     // Optional: Configuration files (if not in SQL dump)
        //     ConfigurationFileSeeder::class,
        // ]);
        
        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   admin@example.com / password');
        $this->command->info('   test@example.com / password');
    }
}
