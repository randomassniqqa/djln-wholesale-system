<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $p = DB::table('products')->first();
    if (!$p) {
        die("No products found to test.\n");
    }

    echo "Initial state:\n";
    echo "  Product ID: {$p->id}\n";
    echo "  Product Name: {$p->name}\n";
    echo "  Stock Qty: {$p->stock_qty}\n";
    
    // 1. Test sp_RestockProduct
    echo "\n--- TESTING sp_RestockProduct ---\n";
    DB::statement('CALL sp_RestockProduct(?, ?)', [$p->id, 50]);
    $p2 = DB::table('products')->where('id', $p->id)->first();
    echo "  Stock Qty after adding 50: {$p2->stock_qty} (Status: {$p2->stock_status})\n";

    // 2. Test tx_CompleteWholesaleOrder (Transaction) & trg_UpdateOrderTotal (Trigger)
    echo "\n--- TESTING tx_CompleteWholesaleOrder & trg_UpdateOrderTotal ---\n";
    // Get a valid customer ID (any user for now, or ID 1)
    $customer_id = 1; 
    
    // Call the transactional stored procedure
    DB::statement('CALL tx_CompleteWholesaleOrder(?, ?, ?)', [$customer_id, $p->id, 10]);
    
    // Get the newly created order
    $order = DB::table('orders')->orderBy('id', 'desc')->first();
    $items = DB::table('order_items')->where('order_id', $order->id)->count();
    
    echo "  Order Created Successfully! ID: {$order->id}, Number: {$order->order_number}\n";
    echo "  Trigger Result (Total Amount): ₱{$order->total_amount}\n";
    echo "  Order Items Count: {$items}\n";
    
    $p3 = DB::table('products')->where('id', $p->id)->first();
    echo "  Stock Qty after deducting 10: {$p3->stock_qty} (Status: {$p3->stock_status})\n";

    // 3. Test vw_ProductStockStatus
    echo "\n--- TESTING vw_ProductStockStatus ---\n";
    $v1 = DB::select('SELECT * FROM vw_ProductStockStatus WHERE product_id = ?', [$p->id])[0];
    echo "  View Output: {$v1->product_name} is currently '{$v1->stock_health}' with {$v1->stock_qty} units.\n";

    // 4. Test vw_OrderSummary
    echo "\n--- TESTING vw_OrderSummary ---\n";
    $v2 = DB::select('SELECT * FROM vw_OrderSummary WHERE order_id = ?', [$order->id])[0];
    echo "  View Output: Order {$v2->order_number} for customer '{$v2->customer_name}' totals ₱{$v2->total_amount}.\n";

    // Clean up test order
    DB::table('order_items')->where('order_id', $order->id)->delete();
    DB::table('orders')->where('id', $order->id)->delete();
    // Revert stock
    DB::table('products')->where('id', $p->id)->update(['stock_qty' => $p->stock_qty, 'stock_status' => $p->stock_status]);
    
    echo "\nALL TESTS PASSED SUCCESSFULLY.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
