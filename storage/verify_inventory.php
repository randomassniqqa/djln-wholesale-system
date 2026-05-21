<?php

/**
 * DJLN Marketing — Inventory Verification Script
 *
 * Run from the project root with:
 *   php artisan tinker --execute='require "storage/verify_inventory.php";'
 *
 * Or interactively inside tinker:
 *   >>> require 'storage/verify_inventory.php'
 */

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;

$line = str_repeat('═', 50);

echo PHP_EOL . $line . PHP_EOL;
echo '  DJLN MARKETING — INVENTORY VERIFICATION' . PHP_EOL;
echo $line . PHP_EOL;

// ── Top-level counts ──────────────────────────────────────
echo PHP_EOL;
echo '📦 Categories : ' . Category::count() . PHP_EOL;
echo '🛒 Products   : ' . Product::count()  . PHP_EOL;
echo '📋 Orders     : ' . Order::count()    . PHP_EOL;

// ── Per-category breakdown ────────────────────────────────
echo PHP_EOL . '─── Category Breakdown ───────────────────────' . PHP_EOL;

foreach (Category::withCount('products')->orderBy('name')->get() as $cat) {
    /** @var \App\Models\Category $cat */
    $inStock  = $cat->products()->where('stock_status', 'in_stock')->count();
    $lowStock = $cat->products()->where('stock_status', 'low_stock')->count();
    $oos      = $cat->products()->where('stock_status', 'out_of_stock')->count();

    printf(
        "  %s  %-25s  %d products  [✅%d  ⚠️%d  ❌%d]%s",
        $cat->icon ?? '📦',
        $cat->name,
        $cat->products_count,
        $inStock,
        $lowStock,
        $oos,
        PHP_EOL
    );
}

// ── Stock alerts ──────────────────────────────────────────
$lowStockProducts = Product::whereColumn('stock_qty', '<=', 'reorder_level')
    ->where('stock_status', '!=', 'discontinued')
    ->get(['name', 'sku', 'stock_qty', 'reorder_level', 'stock_status']);

echo PHP_EOL . '─── Low-Stock Alerts ─────────────────────────' . PHP_EOL;

if ($lowStockProducts->isEmpty()) {
    echo '  ✅ All products are above reorder level.' . PHP_EOL;
} else {
    foreach ($lowStockProducts as $p) {
        printf(
            "  ⚠️  %-25s [%s]  qty: %d  reorder at: %d%s",
            $p->name,
            $p->sku,
            $p->stock_qty,
            $p->reorder_level,
            PHP_EOL
        );
    }
}

// ── Order summary ─────────────────────────────────────────
echo PHP_EOL . '─── Order Summary ────────────────────────────' . PHP_EOL;

$orderStats = \Illuminate\Support\Facades\DB::table('orders')
    ->selectRaw("
        SUM(CASE WHEN order_type = 'wholesale' THEN 1 ELSE 0 END) as wholesale,
        SUM(CASE WHEN order_type = 'retail'    THEN 1 ELSE 0 END) as retail,
        SUM(CASE WHEN payment_status = 'paid'  THEN total_amount ELSE 0 END) as revenue
    ")
    ->first();

echo '  🏭 Wholesale orders : ' . ($orderStats->wholesale ?? 0) . PHP_EOL;
echo '  🛍️  Retail orders    : ' . ($orderStats->retail    ?? 0) . PHP_EOL;
echo '  💰 Total revenue    : ₱' . number_format($orderStats->revenue ?? 0, 2) . PHP_EOL;

echo PHP_EOL . $line . PHP_EOL . PHP_EOL;
