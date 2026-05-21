<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Add estimated_hours column if not exists
            if (!Schema::hasColumn('tasks', 'estimated_hours')) {
                $table->decimal('estimated_hours', 8, 2)->default(0)->after('priority');
            }
        });
        
        // Composite indexes are already created in 2025_01_01_tasks.php
        // Skipping: $table->index(['assigned_user_id', 'status']);
        // Skipping: $table->index(['project_id', 'status']);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['assigned_user_id', 'status']);
            $table->dropIndex(['project_id', 'status']);
            $table->dropColumn('estimated_hours');
        });
    }
};
