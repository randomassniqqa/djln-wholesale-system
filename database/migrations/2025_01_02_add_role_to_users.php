<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add role and indexes to users table for 3-Tier PBAC system
     * ✅ FORENSIC INTEGRITY: Runs AFTER users table exists (core migration)
     * ✅ 3-TIER HIERARCHY: admin, project_manager, team_member, client
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'project_manager', 'team_member', 'client'])
                    ->default('team_member')
                    ->after('password');
                
                $table->index('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropIndex('users_role_index');
                $table->dropColumn('role');
            }
        });
    }
};
