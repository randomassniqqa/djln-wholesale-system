<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::unprepared('DROP VIEW IF EXISTS vw_ProductStockStatus;');
    DB::unprepared('CREATE VIEW vw_ProductStockStatus AS SELECT p.id AS product_id, p.sku, p.name AS product_name, c.name AS category_name, p.stock_qty, p.reorder_level, CASE WHEN p.stock_qty = 0 THEN \'Out of Stock\' WHEN p.stock_qty <= p.reorder_level THEN \'Low Stock\' ELSE \'Healthy\' END AS stock_health FROM products p LEFT JOIN categories c ON p.category_id = c.id;');

    DB::unprepared('DROP VIEW IF EXISTS vw_OrderSummary;');
    DB::unprepared('CREATE VIEW vw_OrderSummary AS SELECT o.id AS order_id, o.order_number, u.name AS customer_name, o.order_type, o.fulfillment_status, o.payment_status, o.total_amount, o.ordered_at FROM orders o LEFT JOIN users u ON o.customer_id = u.id;');

    DB::unprepared('DROP TRIGGER IF EXISTS trg_UpdateOrderTotal;');
    DB::unprepared('CREATE TRIGGER trg_UpdateOrderTotal AFTER INSERT ON order_items FOR EACH ROW BEGIN UPDATE orders SET subtotal = subtotal + NEW.line_total, total_amount = (subtotal + NEW.line_total) - discount_amount WHERE id = NEW.order_id; END;');

    DB::unprepared('DROP PROCEDURE IF EXISTS sp_RestockProduct;');
    DB::unprepared('CREATE PROCEDURE sp_RestockProduct(IN p_product_id BIGINT, IN p_quantity INT) BEGIN IF EXISTS (SELECT 1 FROM products WHERE id = p_product_id) THEN UPDATE products SET stock_qty = stock_qty + p_quantity, stock_status = CASE WHEN (stock_qty + p_quantity) > reorder_level THEN \'in_stock\' WHEN (stock_qty + p_quantity) > 0 THEN \'low_stock\' ELSE \'out_of_stock\' END WHERE id = p_product_id; ELSE SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Product not found.\'; END IF; END;');

    DB::unprepared('DROP PROCEDURE IF EXISTS sp_GetMonthlyWholesaleRevenue;');
    DB::unprepared('CREATE PROCEDURE sp_GetMonthlyWholesaleRevenue(IN p_start_date DATE, IN p_end_date DATE) BEGIN SELECT IFNULL(SUM(total_amount), 0.00) AS wholesale_revenue FROM orders WHERE order_type = \'wholesale\' AND payment_status = \'paid\' AND DATE(ordered_at) BETWEEN p_start_date AND p_end_date; END;');

    DB::unprepared('DROP PROCEDURE IF EXISTS tx_CompleteWholesaleOrder;');
    DB::unprepared('CREATE PROCEDURE tx_CompleteWholesaleOrder(IN p_customer_id BIGINT, IN p_product_id BIGINT, IN p_quantity INT) BEGIN DECLARE v_order_id BIGINT; DECLARE v_unit_price DECIMAL(10,2); DECLARE v_stock_qty INT; DECLARE v_order_number VARCHAR(255); DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END; START TRANSACTION; SELECT stock_qty, wholesale_price INTO v_stock_qty, v_unit_price FROM products WHERE id = p_product_id FOR UPDATE; IF v_stock_qty < p_quantity THEN ROLLBACK; SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Insufficient stock for this product.\'; END IF; SET v_order_number = CONCAT(\'WHS-\', DATE_FORMAT(NOW(), \'%Y%m%d%H%i%s\')); INSERT INTO orders (order_number, customer_id, order_type, fulfillment_status, payment_status, ordered_at, created_at, updated_at) VALUES (v_order_number, p_customer_id, \'wholesale\', \'pending\', \'unpaid\', NOW(), NOW(), NOW()); SET v_order_id = LAST_INSERT_ID(); INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total, created_at, updated_at) VALUES (v_order_id, p_product_id, p_quantity, v_unit_price, (p_quantity * v_unit_price), NOW(), NOW()); UPDATE products SET stock_qty = stock_qty - p_quantity, stock_status = CASE WHEN (stock_qty - p_quantity) <= 0 THEN \'out_of_stock\' WHEN (stock_qty - p_quantity) <= reorder_level THEN \'low_stock\' ELSE \'in_stock\' END WHERE id = p_product_id; COMMIT; END;');
    
    echo "Successfully created all triggers, procedures, and views.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
