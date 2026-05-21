<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CreateTestUserSeeder extends Seeder
{
    /**
     * Seed the application's database with a single verified test user.
     */
    public function run(): void
    {
        // Create verified admin user for testing
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Create verified project manager
        User::firstOrCreate(
            ['email' => 'pm@test.com'],
            [
                'name' => 'Project Manager',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'project_manager',
            ]
        );

        // Create verified team member
        User::firstOrCreate(
            ['email' => 'team@test.com'],
            [
                'name' => 'Team Member',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'team_member',
            ]
        );

        $this->command->info('✅ Test users created successfully!');
        $this->command->info('   Admin: admin@test.com / password');
        $this->command->info('   PM: pm@test.com / password');
        $this->command->info('   Team: team@test.com / password');
    }
}
