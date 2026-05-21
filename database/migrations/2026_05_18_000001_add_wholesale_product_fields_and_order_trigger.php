<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * DELIVERABLE 3: Wholesale Product Enhancements + Order Alert Trigger
     *
     * PART 1 — New product columns:
     *   brand_name       VARCHAR(100) nullable  — manufacturer/brand
     *   weight_volume    VARCHAR(50)  nullable  — e.g. "5 kg", "100 pcs", "2 L"
     *   moq              UNSIGNED INT default 1 — minimum order quantity (wholesale rule)
     *
     * PART 2 — DB trigger: tr_new_order_notification
     *   AFTER INSERT on orders → auto-inserts a notification row for every admin user
     */
    public function up(): void
    {
        // ── PART 1: Wholesale product columns ────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand_name')) {
                $table->string('brand_name', 100)->nullable()->after('description')
                      ->comment('Manufacturer / brand (e.g. Sour Worms Co.)');
            }
            if (!Schema::hasColumn('products', 'weight_volume')) {
                $table->string('weight_volume', 50)->nullable()->after('brand_name')
                      ->comment('Shipping descriptor e.g. "5 kg", "100 pcs", "2 L"');
            }
            if (!Schema::hasColumn('products', 'moq')) {
                $table->unsignedInteger('moq')->default(1)->after('reorder_level')
                      ->comment('Minimum Order Quantity — wholesale buying rule');
            }
        });

        echo "\n✅ Added brand_name, weight_volume, moq to products table\n";

        // ── PART 2: Order-notification trigger ───────────────────────────
        DB::statement('DROP TRIGGER IF EXISTS tr_new_order_notification');

        DB::statement("
            CREATE TRIGGER tr_new_order_notification
            AFTER INSERT ON orders
            FOR EACH ROW
            BEGIN
                -- Insert one notification per admin user so every admin sees the alert
                INSERT INTO notifications (
                    id,
                    type,
                    notifiable_type,
                    notifiable_id,
                    data,
                    read_at,
                    created_at,
                    updated_at
                )
                SELECT
                    UUID(),
                    'App\\\\Notifications\\\\NewOrderAlert',
                    'App\\\\Models\\\\User',
                    u.id,
                    JSON_OBJECT(
                        'order_id',     NEW.id,
                        'order_number', NEW.order_number,
                        'customer_id',  NEW.customer_id,
                        'total_amount', NEW.total_amount,
                        'order_type',   NEW.order_type,
                        'message',      CONCAT(
                            'New Order ',
                            NEW.order_number,
                            ' received for \u20b1',
                            FORMAT(NEW.total_amount, 2)
                        ),
                        'icon',         '🛒',
                        'created_at',   DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
                    ),
                    NULL,
                    NOW(),
                    NOW()
                FROM users u
                WHERE LOWER(u.role) = 'admin';
            END
        ");

        echo "✅ Created trigger: tr_new_order_notification\n";
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS tr_new_order_notification');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['brand_name', 'weight_volume', 'moq'],
                fn ($col) => Schema::hasColumn('products', $col)
            ));
        });
    }
};
