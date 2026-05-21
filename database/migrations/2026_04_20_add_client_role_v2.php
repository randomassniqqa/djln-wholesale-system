<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'client' role to users and client_id to projects
     * ✅ SELECTIVE SCRYING: Clients can access their own projects only
     */
    public function up(): void
    {
        // Add client_id to projects table first (safe operation)
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'client_id')) {
                $table->foreignId('client_id')->nullable()->constrained('users')->cascadeOnDelete()->after('manager_id');
                $table->index('client_id');
            }
        });

        // Handle enum modification for different databases
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: Recreate the column with expanded CHECK constraint
            DB::statement("
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY,
                    name TEXT NOT NULL,
                    email TEXT NOT NULL UNIQUE,
                    password TEXT NOT NULL,
                    role TEXT NOT NULL CHECK(role IN ('admin', 'project_manager', 'team_member', 'client')) DEFAULT 'team_member',
                    email_verified_at DATETIME,
                    remember_token TEXT,
                    created_at DATETIME,
                    updated_at DATETIME
                );
            ");
            DB::statement("
                INSERT INTO users_new 
                SELECT id, name, email, password, role, email_verified_at, remember_token, created_at, updated_at 
                FROM users;
            ");
            DB::statement("DROP TABLE users;");
            DB::statement("ALTER TABLE users_new RENAME TO users;");
            DB::statement("CREATE INDEX users_role_index ON users(role);");
            DB::statement("CREATE INDEX users_email_index ON users(email);");
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL: Add CHECK constraint if not exists
            $constraintExists = DB::selectOne("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = 'users' 
                AND constraint_name = 'users_role_check'
            ");
            
            if (!$constraintExists) {
                DB::statement("
                    ALTER TABLE users 
                    ADD CONSTRAINT users_role_check CHECK(role IN ('admin', 'project_manager', 'team_member', 'client'))
                ");
            }
        } else {
            // MySQL: Modify the enum
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'project_manager', 'team_member', 'client'])->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'client_id')) {
                $table->dropForeign('projects_client_id_foreign');
                $table->dropColumn('client_id');
            }
        });

        // Revert enum modification
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY,
                    name TEXT NOT NULL,
                    email TEXT NOT NULL UNIQUE,
                    password TEXT NOT NULL,
                    role TEXT NOT NULL CHECK(role IN ('admin', 'project_manager', 'team_member')) DEFAULT 'team_member',
                    email_verified_at DATETIME,
                    remember_token TEXT,
                    created_at DATETIME,
                    updated_at DATETIME
                );
            ");
            DB::statement("
                INSERT INTO users_new 
                SELECT id, name, email, password, role, email_verified_at, remember_token, created_at, updated_at 
                FROM users
                WHERE role != 'client';
            ");
            DB::statement("DROP TABLE users;");
            DB::statement("ALTER TABLE users_new RENAME TO users;");
            DB::statement("CREATE INDEX users_role_index ON users(role);");
            DB::statement("CREATE INDEX users_email_index ON users(email);");
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL: Drop the CHECK constraint if it exists
            DB::statement("
                ALTER TABLE users 
                DROP CONSTRAINT IF EXISTS users_role_check
            ");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'project_manager', 'team_member'])->change();
            });
        }
    }
};
