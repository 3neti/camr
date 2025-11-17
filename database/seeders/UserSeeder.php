<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user if doesn't exist
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::factory()->withoutTwoFactor()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'job_title' => 'System Administrator',
                'access_level' => 'all',
                'is_active' => true,
            ]);
            
            $this->command->info('Created admin user: admin@example.com (password: password)');
        }

        // Create regular test user if doesn't exist
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->withoutTwoFactor()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'role' => 'user',
                'job_title' => 'Site Manager',
                'access_level' => 'selected',
                'is_active' => true,
            ]);
            
            $this->command->info('Created test user: test@example.com (password: password)');
        }

        // Create additional demo users
        $userCount = User::count();
        if ($userCount < 8) {
            $jobTitles = [
                'Facility Manager',
                'Energy Analyst',
                'Operations Manager',
                'Maintenance Supervisor',
                'Technical Coordinator',
            ];

            foreach ($jobTitles as $index => $jobTitle) {
                $email = strtolower(str_replace(' ', '.', $jobTitle)).'@example.com';
                
                if (!User::where('email', $email)->exists()) {
                    User::factory()->withoutTwoFactor()->create([
                        'name' => str_replace('.', ' ', ucwords(str_replace('@example.com', '', $email))),
                        'email' => $email,
                        'password' => bcrypt('password'),
                        'role' => $index === 0 ? 'admin' : 'user',
                        'job_title' => $jobTitle,
                        'access_level' => $index === 0 ? 'all' : 'selected',
                        'is_active' => true,
                    ]);
                }
            }

            $this->command->info('Created demo users. Total users: '.User::count());
        } else {
            $this->command->info('Users already seeded. Total: '.User::count());
        }

        // Attach users to sites (for selected access level)
        $users = User::where('access_level', 'selected')->get();
        $sites = Site::all();

        if ($users->isNotEmpty() && $sites->isNotEmpty()) {
            foreach ($users as $user) {
                if ($user->sites()->count() === 0) {
                    // Attach 2-4 random sites to each user
                    $randomSites = $sites->random(min(rand(2, 4), $sites->count()));
                    $user->sites()->attach($randomSites->pluck('id'));
                }
            }
            
            $this->command->info('Attached sites to users with selected access.');
        }
    }
}
