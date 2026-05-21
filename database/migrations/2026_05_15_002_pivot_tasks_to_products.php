<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DJLN Marketing — Pivot: tasks → products
     *
     * Maps old task fields:
     *   title          → name
     *   description    → description
     *   status         → stock_status  (in_stock | low_stock | out_of_stock | discontinued)
     *   priority       → DROPPED (not relevant)
     *   due_date       → expiry_date   (candy shelf life)
     *   assigned_user_id → DROPPED
     *   created_by     → created_by
     *   estimated_hours→ DROPPED
     *
     * New fields added:
     *   sku, wholesale_price, retail_price,
     *   stock_qty, reorder_level, unit, image_url
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();                  // Stock Keeping Unit
            $table->text('description')->nullable();
            $table->string('unit')->default('piece');         // piece | kg | box | pack | set
            $table->decimal('wholesale_price', 10, 2);        // Bulk / dealer price
            $table->decimal('retail_price', 10, 2);           // Walk-in / single-unit price
            $table->unsignedInteger('stock_qty')->default(0); // Current stock level
            $table->unsignedInteger('reorder_level')->default(10); // Alert threshold
            $table->enum('stock_status', [
                'in_stock',
                'low_stock',
                'out_of_stock',
                'discontinued',
            ])->default('in_stock');
            $table->date('expiry_date')->nullable();           // Shelf-life tracking
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamps();

            // Performance indexes
            $table->index('category_id');
            $table->index('sku');
            $table->index('stock_status');
            $table->index('is_active');
            $table->index(['category_id', 'stock_status']);
            $table->index(['stock_qty', 'reorder_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
