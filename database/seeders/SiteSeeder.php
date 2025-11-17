<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        // Create a company if none exists
        if (!Company::exists()) {
            Company::create([
                'code' => 'ABC',
                'name' => 'ABC Corporation',
            ]);
        }
        $company = Company::first();

        // Create a division if none exists
        if (!Division::exists()) {
            Division::create([
                'code' => 'DIV1',
                'name' => 'Main Division',
            ]);
        }
        $division = Division::first();

        // Create sites if less than 10 exist
        $siteCount = Site::count();
        if ($siteCount < 10) {
            $sitesToCreate = 10 - $siteCount;
            for ($i = 1; $i <= $sitesToCreate; $i++) {
                Site::create([
                    'company_id' => $company->id,
                    'division_id' => $division->id,
                    'code' => 'SITE-' . str_pad($siteCount + $i, 3, '0', STR_PAD_LEFT),
                ]);
            }
        }

        $this->command->info('Seeding completed. Total sites: '.Site::count());
    }
}
