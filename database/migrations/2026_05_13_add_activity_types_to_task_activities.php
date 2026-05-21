<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add missing activity types that observers use (updated, deleted, restored)
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Drop existing check constraint and recreate with expanded set
            DB::unprepared("ALTER TABLE task_activities DROP CONSTRAINT IF EXISTS task_activities_activity_type_check;");
            DB::unprepared("ALTER TABLE task_activities ADD CONSTRAINT task_activities_activity_type_check CHECK (activity_type IN ('created','status_changed','priority_changed','assigned','reopened','commented','due_date_changed','updated','deleted','restored'));");
        } else {
            // MySQL: modify enum column to include new values
            DB::unprepared("ALTER TABLE task_activities MODIFY activity_type ENUM('created','status_changed','priority_changed','assigned','reopened','commented','due_date_changed','updated','deleted','restored') NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared("ALTER TABLE task_activities DROP CONSTRAINT IF EXISTS task_activities_activity_type_check;");
            DB::unprepared("ALTER TABLE task_activities ADD CONSTRAINT task_activities_activity_type_check CHECK (activity_type IN ('created','status_changed','priority_changed','assigned','reopened','commented','due_date_changed'));");
        } else {
            DB::unprepared("ALTER TABLE task_activities MODIFY activity_type ENUM('created','status_changed','priority_changed','assigned','reopened','commented','due_date_changed') NOT NULL");
        }
    }
};
