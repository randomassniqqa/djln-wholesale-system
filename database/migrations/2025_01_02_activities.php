<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('activity_type', ['created', 'status_changed', 'priority_changed', 'assigned', 'reopened', 'commented', 'due_date_changed']);
            $table->text('description')->nullable();
            $table->timestamp('activity_date')->useCurrent();
            $table->timestamps();

            // Indexes for efficient queries and reporting
            $table->index('task_id');
            $table->index('user_id');
            $table->index('activity_type');
            $table->index('activity_date');
            $table->index(['task_id', 'activity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
