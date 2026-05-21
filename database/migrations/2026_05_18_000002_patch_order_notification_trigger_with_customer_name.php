<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PATCH: Rebuild tr_new_order_notification with full customer name.
     *
     * Original message: "New Order ORD-001 received for ₱2,650"
     * Updated message:  "New Order #ORD-001 received from John Doe for ₱2,650"
     *
     * Uses a correlated subquery to look up the customer name at trigger time.
     */
    public function up(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS tr_new_order_notification');

        DB::statement("
            CREATE TRIGGER tr_new_order_notification
            AFTER INSERT ON orders
            FOR EACH ROW
            BEGIN
                DECLARE v_customer_name VARCHAR(255) DEFAULT 'Unknown';

                -- Look up the customer name at insert time
                SELECT name INTO v_customer_name
                FROM users
                WHERE id = NEW.customer_id
                LIMIT 1;

                -- Insert one notification row per admin user
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
                        'order_id',      NEW.id,
                        'order_number',  NEW.order_number,
                        'customer_id',   NEW.customer_id,
                        'customer_name', v_customer_name,
                        'total_amount',  NEW.total_amount,
                        'order_type',    NEW.order_type,
                        'message',       CONCAT(
                            'New Order #',
                            NEW.order_number,
                            ' received from ',
                            v_customer_name,
                            ' for \u20b1',
                            FORMAT(NEW.total_amount, 2)
                        ),
                        'icon',          '\ud83d\uded2',
                        'created_at',    DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
                    ),
                    NULL,
                    NOW(),
                    NOW()
                FROM users u
                WHERE LOWER(u.role) = 'admin';
            END
        ");

        echo "\n✅ Rebuilt trigger: tr_new_order_notification (now includes customer name)\n";
    }

    public function down(): void
    {
        // Restore previous version (without customer name) on rollback
        DB::statement('DROP TRIGGER IF EXISTS tr_new_order_notification');

        DB::statement("
            CREATE TRIGGER tr_new_order_notification
            AFTER INSERT ON orders
            FOR EACH ROW
            BEGIN
                INSERT INTO notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at)
                SELECT UUID(), 'App\\\\Notifications\\\\NewOrderAlert', 'App\\\\Models\\\\User', u.id,
                    JSON_OBJECT(
                        'order_id', NEW.id, 'order_number', NEW.order_number,
                        'customer_id', NEW.customer_id, 'total_amount', NEW.total_amount,
                        'order_type', NEW.order_type,
                        'message', CONCAT('New Order ', NEW.order_number, ' received for \u20b1', FORMAT(NEW.total_amount, 2)),
                        'icon', '\ud83d\uded2', 'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
                    ),
                    NULL, NOW(), NOW()
                FROM users u WHERE LOWER(u.role) = 'admin';
            END
        ");
    }
};
