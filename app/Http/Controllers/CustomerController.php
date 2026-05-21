<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * DJLN Marketing — CustomerController
 *
 * Handles the customer-facing "shop" experience:
 *   GET  /shop                  → dashboard (welcome + recent orders + buy-again)
 *   GET  /shop/catalog          → product grid with category tabs
 *   GET  /shop/cart             → cart summary page
 *   POST /shop/order            → place order (atomic transaction)
 *   GET  /shop/orders/{order}   → order confirmation / status
 */
class CustomerController extends Controller
{
    // ──────────────────────────────────────────────
    // DASHBOARD — Welcome screen for the customer
    // ──────────────────────────────────────────────

    public function dashboard(): View
    {
        $user = auth()->user();

        // Most recent order
        $latestOrder = Order::where('customer_id', $user->id)
            ->with('items.product')
            ->latest('ordered_at')
            ->first();

        // Top 4 most-re-ordered products (buy-again)
        $buyAgain = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('order', fn ($q) => $q->where('customer_id', $user->id))
            ->with('product.category')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(4)
            ->get()
            ->filter(fn ($item) => $item->product && $item->product->is_active)
            ->values();

        // Recent orders (last 5)
        $recentOrders = Order::where('customer_id', $user->id)
            ->withCount('items')
            ->latest('ordered_at')
            ->limit(5)
            ->get();

        return view('customer.dashboard', compact('latestOrder', 'buyAgain', 'recentOrders'));
    }

    // ──────────────────────────────────────────────
    // CATALOG — Product grid with category filter
    // ──────────────────────────────────────────────

    public function catalog(Request $request): View
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name', 'icon', 'slug']);

        $query = Product::with('category')
            ->active()
            ->where('stock_status', '!=', 'discontinued');

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $products = $query->orderBy('name')->get();

        $activeCategory = $request->category
            ? $categories->firstWhere('id', (int) $request->category)
            : null;

        return view('customer.catalog', compact('products', 'categories', 'activeCategory'));
    }

    // ──────────────────────────────────────────────
    // CART — Display cart contents (session-backed)
    // ──────────────────────────────────────────────

    public function cart(): View
    {
        $cartItems = $this->buildCartItems();
        $subtotal  = collect($cartItems)->sum('line_total');

        return view('customer.cart', compact('cartItems', 'subtotal'));
    }

    // ──────────────────────────────────────────────
    // ADD TO CART — AJAX-friendly session handler
    // POST /shop/cart/add
    // ──────────────────────────────────────────────

    public function addToCart(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $cart = session()->get('djln_cart', []);
        $pid  = (int) $request->product_id;
        $qty  = (int) $request->quantity;

        $cart[$pid] = ($cart[$pid] ?? 0) + $qty;
        session()->put('djln_cart', $cart);

        return redirect()
            ->route('customer.catalog')
            ->with('success', 'Added to cart! 🛒');
    }

    // ──────────────────────────────────────────────
    // REMOVE FROM CART
    // POST /shop/cart/remove
    // ──────────────────────────────────────────────

    public function removeFromCart(Request $request): RedirectResponse
    {
        $request->validate(['product_id' => ['required', 'integer']]);

        $cart = session()->get('djln_cart', []);
        unset($cart[(int) $request->product_id]);
        session()->put('djln_cart', $cart);

        return back()->with('success', 'Item removed from cart.');
    }

    // ──────────────────────────────────────────────
    // PLACE ORDER — Atomic transaction + stock deduction
    // POST /shop/order
    // ──────────────────────────────────────────────

    public function placeOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'order_type' => ['required', 'in:wholesale,retail'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $cartItems = $this->buildCartItems();

        if (empty($cartItems)) {
            return back()->with('error', 'Your cart is empty.');
        }

        $orderType = $request->order_type;

        // Pre-check stock
        foreach ($cartItems as $item) {
            if ($item['product']->stock_qty < $item['quantity']) {
                return back()->with('error',
                    "Sorry, \"{$item['product']->name}\" only has {$item['product']->stock_qty} units left."
                );
            }
        }

        $order = DB::transaction(function () use ($request, $orderType, $cartItems) {
            $order = Order::create([
                'order_number'       => Order::generateOrderNumber(),
                'customer_id'        => auth()->id(),
                'processed_by'       => auth()->id(),
                'order_type'         => $orderType,
                'fulfillment_status' => 'pending',
                'payment_status'     => 'unpaid',
                'subtotal'           => 0,
                'discount_amount'    => 0,
                'total_amount'       => 0,
                'notes'              => $request->notes,
                'ordered_at'         => now(),
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => (string) round($item['unit_price'] * $item['quantity'], 2),
                ]);

                $item['product']->deductStock($item['quantity']);
            }

            $order->recalculateTotals();

            return $order;
        });

        // Clear cart after successful order
        session()->forget('djln_cart');

        return redirect()
            ->route('customer.order-confirmation', $order)
            ->with('success', "Order {$order->order_number} placed successfully! 🎉");
    }

    // ──────────────────────────────────────────────
    // ORDER CONFIRMATION — Success & status page
    // GET /shop/orders/{order}
    // ──────────────────────────────────────────────

    public function orderConfirmation(Order $order): View
    {
        // Customers can only see their own orders
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        $order->load('items.product.category');

        return view('customer.confirmation', compact('order'));
    }

    // ──────────────────────────────────────────────
    // PRIVATE — Build cart item list from session
    // ──────────────────────────────────────────────

    private function buildCartItems(): array
    {
        $cart   = session()->get('djln_cart', []);
        $result = [];

        if (empty($cart)) {
            return $result;
        }

        $products = Product::whereIn('id', array_keys($cart))->with('category')->get()->keyBy('id');

        foreach ($cart as $pid => $qty) {
            if (! $products->has($pid)) {
                continue;
            }
            $product    = $products[$pid];
            $unitPrice  = (float) $product->wholesale_price; // default to wholesale for shop
            $result[]   = [
                'product'    => $product,
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $qty, 2),
            ];
        }

        return $result;
    }
}
