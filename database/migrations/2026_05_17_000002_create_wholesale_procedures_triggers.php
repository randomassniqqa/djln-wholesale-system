<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * DELIVERABLE 2: WHOLESALE INVENTORY LOGIC
     * 
     * Creates 2 Stored Procedures:
     *   1. proc_calculate_order_totals() — Recalculate order amounts from items
     *   2. proc_deduct_inventory() — Deduct stock from products (atomic)
     *
     * Creates 3 Triggers:
     *   1. tr_order_items_insert — Auto-sync product stock when item added
     *   2. tr_order_items_update — Adjust stock if item qty changed
     *   3. tr_order_status_change — Update fulfillment status
     *
     * Transaction Wrapper: DB::transaction() in OrderController::store()
     */
    public function up(): void
    {
        // ──────────────────────────────────────────────────────────
        // PROCEDURE 1: Calculate Order Totals
        // ──────────────────────────────────────────────────────────
        DB::statement("
            DROP PROCEDURE IF EXISTS proc_calculate_order_totals
        ");

        DB::statement("
            CREATE PROCEDURE proc_calculate_order_totals(IN p_order_id INT)
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Calculate subtotal from order_items
                UPDATE orders o
                SET subtotal = COALESCE((
                    SELECT SUM(oi.line_total) 
                    FROM order_items oi 
                    WHERE oi.order_id = p_order_id
                ), 0)
                WHERE o.id = p_order_id;
                
                -- Recalculate total_amount = subtotal - discount_amount
                UPDATE orders
                SET total_amount = GREATEST(0, subtotal - COALESCE(discount_amount, 0))
                WHERE id = p_order_id;
                
                COMMIT;
            END
        ");

        // ──────────────────────────────────────────────────────────
        // PROCEDURE 2: Deduct Inventory (Atomic)
        // ──────────────────────────────────────────────────────────
        DB::statement("
            DROP PROCEDURE IF EXISTS proc_deduct_inventory
        ");

        DB::statement("
            CREATE PROCEDURE proc_deduct_inventory(
                IN p_product_id INT,
                IN p_quantity INT,
                IN p_order_id INT
            )
            BEGIN
                DECLARE v_current_stock INT DEFAULT 0;
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Get current stock with row lock
                SELECT stock_qty INTO v_current_stock
                FROM products
                WHERE id = p_product_id
                FOR UPDATE;
                
                -- Check if sufficient stock
                IF v_current_stock < p_quantity THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock';
                END IF;
                
                -- Deduct stock
                UPDATE products
                SET stock_qty = stock_qty - p_quantity,
                    stock_status = CASE
                        WHEN (stock_qty - p_quantity) = 0 THEN 'out_of_stock'
                        WHEN (stock_qty - p_quantity) <= reorder_level THEN 'low_stock'
                        ELSE 'in_stock'
                    END,
                    updated_at = NOW()
                WHERE id = p_product_id;
                
                -- Log audit record
                INSERT INTO audit_logs (model_type, model_id, user_id, action, old_values, new_values, created_at, updated_at)
                SELECT 'Product', p_product_id, processed_by, 'stock_deduction',
                    JSON_OBJECT('qty', v_current_stock),
                    JSON_OBJECT('qty', v_current_stock - p_quantity, 'order_id', p_order_id),
                    NOW(), NOW()
                FROM orders WHERE id = p_order_id;
                
                COMMIT;
            END
        ");

        // ──────────────────────────────────────────────────────────
        // TRIGGER 1: Auto-deduct stock when order_item inserted
        // ──────────────────────────────────────────────────────────
        DB::statement("DROP TRIGGER IF EXISTS tr_order_items_insert");

        DB::statement("
            CREATE TRIGGER tr_order_items_insert
            AFTER INSERT ON order_items
            FOR EACH ROW
            BEGIN
                DECLARE v_order_id INT;
                SELECT order_id INTO v_order_id FROM order_items WHERE id = NEW.id LIMIT 1;
                
                -- Only auto-deduct for 'pending' or 'processing' orders
                IF EXISTS (SELECT 1 FROM orders WHERE id = v_order_id AND fulfillment_status IN ('pending', 'processing')) THEN
                    CALL proc_deduct_inventory(NEW.product_id, NEW.quantity, v_order_id);
                END IF;
                
                -- Recalculate order totals
                CALL proc_calculate_order_totals(v_order_id);
            END
        ");

        // ──────────────────────────────────────────────────────────
        // TRIGGER 2: Adjust stock when order_item quantity updated
        // ──────────────────────────────────────────────────────────
        DB::statement("DROP TRIGGER IF EXISTS tr_order_items_update");

        DB::statement("
            CREATE TRIGGER tr_order_items_update
            AFTER UPDATE ON order_items
            FOR EACH ROW
            BEGIN
                DECLARE v_qty_diff INT;
                DECLARE v_order_id INT;
                
                SET v_qty_diff = NEW.quantity - OLD.quantity;
                SELECT order_id INTO v_order_id FROM order_items WHERE id = NEW.id LIMIT 1;
                
                -- If quantity increased, deduct more stock
                IF v_qty_diff > 0 THEN
                    CALL proc_deduct_inventory(NEW.product_id, v_qty_diff, v_order_id);
                -- If quantity decreased, refund stock
                ELSEIF v_qty_diff < 0 THEN
                    UPDATE products
                    SET stock_qty = stock_qty - v_qty_diff,
                        stock_status = CASE
                            WHEN stock_qty - v_qty_diff > reorder_level THEN 'in_stock'
                            WHEN stock_qty - v_qty_diff > 0 THEN 'low_stock'
                            ELSE 'out_of_stock'
                        END,
                        updated_at = NOW()
                    WHERE id = NEW.product_id;
                END IF;
                
                -- Recalculate order totals
                CALL proc_calculate_order_totals(v_order_id);
            END
        ");

        // ──────────────────────────────────────────────────────────
        // TRIGGER 3: Update fulfillment status on payment status change
        // ──────────────────────────────────────────────────────────
        DB::statement("DROP TRIGGER IF EXISTS tr_order_status_change");

        DB::statement("
            CREATE TRIGGER tr_order_status_change
            AFTER UPDATE ON orders
            FOR EACH ROW
            BEGIN
                -- If payment status changes to 'paid' and order is still pending, move to processing
                IF OLD.payment_status != 'paid' AND NEW.payment_status = 'paid' THEN
                    IF NEW.fulfillment_status = 'pending' THEN
                        UPDATE orders
                        SET fulfillment_status = 'processing', updated_at = NOW()
                        WHERE id = NEW.id;
                    END IF;
                END IF;
                
                -- Log status change in audit_logs
                IF OLD.fulfillment_status != NEW.fulfillment_status THEN
                    INSERT INTO audit_logs (model_type, model_id, user_id, action, old_values, new_values, created_at, updated_at)
                    VALUES ('Order', NEW.id, NEW.processed_by, 'fulfillment_status_change',
                        JSON_OBJECT('status', OLD.fulfillment_status),
                        JSON_OBJECT('status', NEW.fulfillment_status),
                        NOW(), NOW());
                END IF;
            END
        ");

        // Add foreign key constraints to ensure referential integrity
        if (!$this->foreignKeyExists('orders', 'customer_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict');
            });
        }

        if (!$this->foreignKeyExists('order_items', 'product_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->onDelete('restrict');
            });
        }

        if (!$this->foreignKeyExists('order_items', 'order_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->onDelete('cascade');
            });
        }

        echo "\n✅ Created 2 Procedures, 3 Triggers, and Foreign Key Constraints\n";
    }

    public function down(): void
    {
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS tr_order_items_insert');
        DB::statement('DROP TRIGGER IF EXISTS tr_order_items_update');
        DB::statement('DROP TRIGGER IF EXISTS tr_order_status_change');

        // Drop procedures
        DB::statement('DROP PROCEDURE IF EXISTS proc_calculate_order_totals');
        DB::statement('DROP PROCEDURE IF EXISTS proc_deduct_inventory');
    }

    /**
     * Check if a foreign key already exists
     */
    private function foreignKeyExists(string $table, string $column): bool
    {
        $constraint = DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
        return !empty($constraint);
    }
};
