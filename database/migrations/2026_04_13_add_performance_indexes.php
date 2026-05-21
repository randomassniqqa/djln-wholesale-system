<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance-critical composite indexes
     */
    public function up(): void
    {
        // Add composite index for projects status + priority filtering
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['status', 'priority', 'due_date']);
        });

        // Add created_at index for sorting operations
        Schema::table('projects', function (Blueprint $table) {
            $table->index('created_at');
        });

        // Add composite index for task filtering by project and status
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['project_id', 'assigned_user_id', 'status']);
        });

        // Add created_at index for task queries
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('created_at');
        });

        // Add composite index for comment queries
        Schema::table('task_comments', function (Blueprint $table) {
            $table->index(['task_id', 'created_at']);
        });

        // Add composite index for activity queries
        Schema::table('task_activities', function (Blueprint $table) {
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new indexes
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority', 'due_date']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'assigned_user_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropIndex(['task_id', 'created_at']);
        });

        Schema::table('task_activities', function (Blueprint $table) {
            $table->dropIndex(['task_id', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
