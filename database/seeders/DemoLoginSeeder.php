<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLoginSeeder extends Seeder
{
    /**
     * Ensure the demo login accounts exist with the expected credentials.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Admin', 'email' => 'admin', 'role' => 'admin'],
            ['name' => 'Sarah', 'email' => 'sarah', 'role' => 'project_manager'],
            ['name' => 'Alex', 'email' => 'alex', 'role' => 'team_member'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'role' => $account['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}