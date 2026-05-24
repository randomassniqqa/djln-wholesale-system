<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DJLN Marketing — Rename tables to reflect wholesale business logic.
     *
     * Rename map:
     *   categories  → product_categories
     *   products    → inventory_items
     *   orders      → wholesale_transactions
     *   order_items → transaction_line_items
     *   audit_logs  → system_audit_trail
     *
     * NOTE: Foreign key constraints follow the rename automatically in InnoDB
     * because MySQL renames the FK along with the table. No FK drops needed.
     */
    public function up(): void
    {
        Schema::rename('categories',  'product_categories');
        Schema::rename('products',    'inventory_items');
        Schema::rename('orders',      'wholesale_transactions');
        Schema::rename('order_items', 'transaction_line_items');
        Schema::rename('audit_logs',  'system_audit_trail');
    }

    public function down(): void
    {
        // Reverse — restores original names
        Schema::rename('product_categories',    'categories');
        Schema::rename('inventory_items',       'products');
        Schema::rename('wholesale_transactions','orders');
        Schema::rename('transaction_line_items','order_items');
        Schema::rename('system_audit_trail',    'audit_logs');
    }
};
