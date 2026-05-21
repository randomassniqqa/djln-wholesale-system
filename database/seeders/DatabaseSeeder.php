<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * ✅ DJLN Marketing Wholesale System
     * - 1 Admin
     * - 3 Staff accounts
     * - 10 Team Members
     * - 2 Clients
     * - Products & Categories via WholesaleSeeder
     */
    public function run(): void
    {
        // ====== CREATE USERS ======

        // 1 Admin
        $admin = User::factory()
            ->admin()
            ->create([
                'name'     => 'Admin',
                'email'    => 'admin',
                'password' => bcrypt('password'),
            ]);

        // 3 Staff / Managers
        $pm1 = User::factory()
            ->projectManager()
            ->create([
                'name'     => 'Sarah',
                'email'    => 'sarah',
                'password' => bcrypt('password'),
            ]);

        $pm2 = User::factory()
            ->projectManager()
            ->create([
                'name'     => 'Michael',
                'email'    => 'michael',
                'password' => bcrypt('password'),
            ]);

        $pm3 = User::factory()
            ->projectManager()
            ->create([
                'name'     => 'Emma',
                'email'    => 'emma',
                'password' => bcrypt('password'),
            ]);

        // 10 Team Members
        $teamMembers = collect([
            User::factory()->teamMember()->create(['name' => 'Alex',    'email' => 'alex',    'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'Jessica', 'email' => 'jessica', 'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'David',   'email' => 'david',   'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'Lisa',    'email' => 'lisa',    'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'James',   'email' => 'james',   'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'Nicole',  'email' => 'nicole',  'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'Robert',  'email' => 'robert',  'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'Angela',  'email' => 'angela',  'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'Marcus',  'email' => 'marcus',  'password' => bcrypt('password')]),
            User::factory()->teamMember()->create(['name' => 'Rachel',  'email' => 'rachel',  'password' => bcrypt('password')]),
        ]);

        // 2 Clients — wholesale party supply business demo accounts
        $client1 = User::factory()
            ->client()
            ->create([
                'name'     => 'Balloon Bash Co.',
                'email'    => 'balloonbash',
                'password' => bcrypt('password'),
            ]);

        $client2 = User::factory()
            ->client()
            ->create([
                'name'     => 'Sweet Occasions',
                'email'    => 'sweetoccasions',
                'password' => bcrypt('password'),
            ]);

        // ====== SUCCESS MESSAGE ======
        $this->command->info('');
        $this->command->info('✅ DJLN MARKETING — USERS CREATED');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('👑 Admin:');
        $this->command->info("   Email: {$admin->email}  |  Password: password");
        $this->command->info('');
        $this->command->info('👤 Staff (3):');
        $this->command->info("   • {$pm1->name} ({$pm1->email})");
        $this->command->info("   • {$pm2->name} ({$pm2->email})");
        $this->command->info("   • {$pm3->name} ({$pm3->email})");
        $this->command->info('');
        $this->command->info('⚔️  Team Members (10): Alex, Jessica, David … and more');
        $this->command->info('');
        $this->command->info('🏢 Clients (2):');
        $this->command->info("   • {$client1->name}");
        $this->command->info("   • {$client2->name}");
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('Password for all users: password');
        $this->command->info('');

        // ── DJLN Marketing Inventory Catalogue ──────────────────────
        $this->call(WholesaleSeeder::class);
    }
}
