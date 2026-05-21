<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DJLN Marketing — Orders table
     *
     * An order belongs to a customer (user with role = 'customer').
     * An order has many order_items, each referencing a product.
     * order_type: wholesale | retail
     * fulfillment_status maps to old task_status logic:
     *   pending → processing → shipped → delivered | cancelled
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g. DJLN-2026-00001
            $table->foreignId('customer_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->foreignId('processed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->enum('order_type', ['wholesale', 'retail'])->default('retail');
            $table->enum('fulfillment_status', [
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
            ])->default('pending');
            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
            ])->default('unpaid');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('customer_id');
            $table->index('fulfillment_status');
            $table->index('order_type');
            $table->index('payment_status');
            $table->index(['customer_id', 'fulfillment_status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);  // Snapshot of price at time of order
            $table->decimal('line_total', 10, 2);   // quantity × unit_price
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
