<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add contact_number (nullable, 20 chars) to the users table.
     * Supports PH mobile format (e.g. 09171234567) and international (+63 917 123 4567).
     * Placed after the 'email' column for logical grouping.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number', 20)
                      ->nullable()
                      ->after('email')
                      ->comment('PH mobile or landline — e.g. 09171234567');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'contact_number')) {
                $table->dropColumn('contact_number');
            }
        });
    }
};
