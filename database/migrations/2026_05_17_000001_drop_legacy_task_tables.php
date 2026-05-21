<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * DJLN Marketing Transition: Drop all legacy Task Management system tables.
     * The system has transitioned from Task Management to Wholesale Inventory.
     * 
     * Tables to drop:
     *   - task_attachments (depends on tasks)
     *   - task_comments (depends on tasks)
     *   - task_activities (depends on tasks)
     *   - tasks (main legacy table)
     */
    public function up(): void
    {
        // Drop in reverse dependency order
        if (Schema::hasTable('task_attachments')) {
            Schema::dropIfExists('task_attachments');
        }

        if (Schema::hasTable('task_comments')) {
            Schema::dropIfExists('task_comments');
        }

        if (Schema::hasTable('task_activities')) {
            Schema::dropIfExists('task_activities');
        }

        // Drop trigger before dropping tasks table (MySQL/MariaDB syntax)
        try {
            DB::statement('DROP TRIGGER IF EXISTS auto_complete_project_on_tasks_done');
        } catch (\Exception $e) {
            // Trigger doesn't exist or already dropped - continue
        }

        if (Schema::hasTable('tasks')) {
            Schema::dropIfExists('tasks');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not restore legacy tables - this is a one-way transition to wholesale system
    }
};
