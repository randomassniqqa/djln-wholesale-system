<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * DJLN Marketing — InventoryDashboardController
 *
 * Replaces the old DashboardController.
 * Surfaces five core KPI groups for the Inventory & Sales Overview:
 *
 *   1. Sales Metrics      — total revenue, today's sales, monthly sales
 *   2. Order Metrics      — pending, processing, delivered counts
 *   3. Stock Metrics      — total products, low-stock alerts, out-of-stock
 *   4. Category Breakdown — product count & stock value per category
 *   5. Recent Activity    — latest 10 orders with items
 */
class InventoryDashboardController extends Controller
{
    public function index(): View
    {
        try {
            // ── 1. SALES METRICS ──────────────────────────────────────
            $totalRevenue = Order::where('payment_status', 'paid')
                ->sum('total_amount');

            $todaySales = Order::where('payment_status', 'paid')
                ->whereDate('ordered_at', today())
                ->sum('total_amount');

            $monthlySales = Order::where('payment_status', 'paid')
                ->thisMonth()
                ->sum('total_amount');

            $wholesaleRevenue = Order::wholesale()
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            $retailRevenue = Order::retail()
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            // ── 2. ORDER METRICS ──────────────────────────────────────
            $orderCounts = DB::table('wholesale_transactions')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN fulfillment_status = 'pending'    THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN fulfillment_status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN fulfillment_status = 'shipped'    THEN 1 ELSE 0 END) as shipped,
                    SUM(CASE WHEN fulfillment_status = 'delivered'  THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN fulfillment_status = 'cancelled'  THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN order_type = 'wholesale'          THEN 1 ELSE 0 END) as wholesale_count,
                    SUM(CASE WHEN order_type = 'retail'             THEN 1 ELSE 0 END) as retail_count
                ")
                ->first();

            // ── 3. STOCK / PRODUCT METRICS ────────────────────────────
            $stockStats = DB::table('inventory_items')
                ->where('is_active', true)
                ->selectRaw("
                    COUNT(*) as total_products,
                    SUM(CASE WHEN stock_status = 'in_stock'     THEN 1 ELSE 0 END) as in_stock,
                    SUM(CASE WHEN stock_status = 'low_stock'    THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN stock_status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN stock_status = 'discontinued' THEN 1 ELSE 0 END) as discontinued,
                    SUM(stock_qty * retail_price)                                  as total_inventory_value,
                    SUM(stock_qty * wholesale_price)                               as total_cost_value
                ")
                ->first();

            // Products at or below reorder level
            $lowStockProducts = Product::active()
                ->lowStock()
                ->with('category:id,name,icon')
                ->orderBy('stock_qty')
                ->limit(8)
                ->get(['id', 'name', 'sku', 'stock_qty', 'reorder_level',
                       'stock_status', 'category_id', 'unit']);

            // ── 4. CATEGORY BREAKDOWN ─────────────────────────────────
            $categoryBreakdown = Category::active()
                ->withCount('products')
                ->with(['products' => function ($q) {
                    $q->select('id', 'category_id', 'stock_qty',
                               'retail_price', 'wholesale_price', 'stock_status');
                }])
                ->orderBy('name')
                ->get()
                ->map(function (Category $cat) {
                    $products = $cat->products;
                    return [
                        'id'            => $cat->id,
                        'name'          => $cat->name,
                        'icon'          => $cat->icon,
                        'product_count' => $cat->products_count,
                        'in_stock'      => $products->where('stock_status', 'in_stock')->count(),
                        'low_stock'     => $products->where('stock_status', 'low_stock')->count(),
                        'out_of_stock'  => $products->where('stock_status', 'out_of_stock')->count(),
                        'retail_value'  => round($products->sum(fn($p) => $p->stock_qty * $p->retail_price), 2),
                    ];
                });

            // ── 5. RECENT ORDERS ─────────────────────────────────────
            $recentOrders = Order::with([
                    'customer:id,name',
                    'items.product:id,name,sku',
                ])
                ->latest('ordered_at')
                ->limit(10)
                ->get(['id', 'order_number', 'customer_id', 'order_type',
                       'fulfillment_status', 'payment_status',
                       'total_amount', 'ordered_at']);

            // ── 6. TOP SELLING PRODUCTS (this month) ──────────────────
            $topProducts = DB::table('transaction_line_items')
                ->join('wholesale_transactions', 'wholesale_transactions.id', '=', 'transaction_line_items.order_id')
                ->join('inventory_items', 'inventory_items.id', '=', 'transaction_line_items.product_id')
                ->whereMonth('wholesale_transactions.ordered_at', now()->month)
                ->whereYear('wholesale_transactions.ordered_at', now()->year)
                ->selectRaw('
                    inventory_items.id,
                    inventory_items.name,
                    inventory_items.sku,
                    SUM(transaction_line_items.quantity)   as total_qty_sold,
                    SUM(transaction_line_items.line_total) as total_revenue
                ')
                ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.sku')
                ->orderByDesc('total_revenue')
                ->limit(5)
                ->get();
            // ── 7. CHART DATA (Last 30 Days) ──────────────────────────
            $chartData = Order::where('payment_status', 'paid')
                ->where('ordered_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(ordered_at) as date, SUM(total_amount) as total')
                ->groupBy('date')
                ->get();

            return view('dashboard.inventory', compact(
                'totalRevenue',
                'todaySales',
                'monthlySales',
                'wholesaleRevenue',
                'retailRevenue',
                'orderCounts',
                'stockStats',
                'lowStockProducts',
                'categoryBreakdown',
                'recentOrders',
                'topProducts',
                'chartData'
            ));

        } catch (Exception $e) {
            Log::error('InventoryDashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Fail gracefully — return view with empty/zero defaults
            return view('dashboard.inventory', [
                'totalRevenue'      => 0,
                'todaySales'        => 0,
                'monthlySales'      => 0,
                'wholesaleRevenue'  => 0,
                'retailRevenue'     => 0,
                'orderCounts'       => (object) [
                    'total' => 0, 'pending' => 0, 'processing' => 0,
                    'shipped' => 0, 'delivered' => 0, 'cancelled' => 0,
                    'wholesale_count' => 0, 'retail_count' => 0,
                ],
                'stockStats'        => (object) [
                    'total_products' => 0, 'in_stock' => 0, 'low_stock' => 0,
                    'out_of_stock' => 0, 'discontinued' => 0,
                    'total_inventory_value' => 0, 'total_cost_value' => 0,
                ],
                'lowStockProducts'  => collect(),
                'categoryBreakdown' => collect(),
                'recentOrders'      => collect(),
                'topProducts'       => collect(),
                'chartData'         => collect(),
            ]);
        }
    }
}
