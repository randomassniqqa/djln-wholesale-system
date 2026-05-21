<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align project delete protection with the intended rule:
     * only active or critical projects are protected.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared("\n                CREATE OR REPLACE FUNCTION prevent_active_project_deletion()\n                RETURNS TRIGGER AS \$\$\n                BEGIN\n                    IF OLD.status = 'active' OR OLD.priority = 'critical' THEN\n                        RAISE EXCEPTION 'DATABASE INTEGRITY VIOLATION: Cannot delete active or critical projects. Deactivate project first.';\n                    END IF;\n                    RETURN OLD;\n                END;\n                \$\$ LANGUAGE plpgsql;\n            ");
        } else {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_active_project_deletion');
            DB::unprepared("\n                CREATE TRIGGER prevent_active_project_deletion\n                BEFORE DELETE ON projects\n                FOR EACH ROW\n                BEGIN\n                    IF OLD.status = 'active' OR OLD.priority = 'critical' THEN\n                        SIGNAL SQLSTATE '45000'\n                        SET MESSAGE_TEXT = 'DATABASE INTEGRITY VIOLATION: Cannot delete active or critical projects. Deactivate project first.';\n                    END IF;\n                END\n            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared("\n                CREATE OR REPLACE FUNCTION prevent_active_project_deletion()\n                RETURNS TRIGGER AS \$\$\n                BEGIN\n                    IF OLD.status IN ('active', 'planning') OR OLD.priority = 'critical' THEN\n                        RAISE EXCEPTION 'DATABASE INTEGRITY VIOLATION: Cannot delete projects with active/critical status. Deactivate project first.';\n                    END IF;\n                    RETURN OLD;\n                END;\n                \$\$ LANGUAGE plpgsql;\n            ");
        } else {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_active_project_deletion');
            DB::unprepared("\n                CREATE TRIGGER prevent_active_project_deletion\n                BEFORE DELETE ON projects\n                FOR EACH ROW\n                BEGIN\n                    IF OLD.status IN ('active', 'planning') OR OLD.priority = 'critical' THEN\n                        SIGNAL SQLSTATE '45000'\n                        SET MESSAGE_TEXT = 'DATABASE INTEGRITY VIOLATION: Cannot delete projects with active/critical status. Deactivate project first.';\n                    END IF;\n                END\n            ");
        }
    }
};