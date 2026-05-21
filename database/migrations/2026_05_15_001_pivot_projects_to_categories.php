<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * DJLN Marketing — Pivot: projects → categories
     * Maps the old "projects" concept to product categories
     * (e.g., Candies, Gummies, Party Needs, Balloons).
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g. "Candies & Gummies"
            $table->text('description')->nullable();
            $table->string('slug')->unique();                // URL-friendly identifier
            $table->string('icon')->nullable();              // emoji or icon class
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
