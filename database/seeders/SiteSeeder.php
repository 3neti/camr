<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test user if none exists
        if (! User::exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create a company if none exists
        $company = Company::first() ?: Company::factory()->create();

        // Create a division if none exists
        $division = Division::first() ?: Division::factory()->create();

        // Create sites if less than 10 exist
        $siteCount = Site::count();
        if ($siteCount < 10) {
            Site::factory(10 - $siteCount)->create([
                'company_id' => $company->id,
                'division_id' => $division->id,
            ]);
        }

        $this->command->info('Seeding completed. Total sites: '.Site::count());
    }
}
