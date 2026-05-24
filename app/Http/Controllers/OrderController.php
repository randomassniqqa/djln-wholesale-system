<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * DJLN Marketing — OrderController
 *
 * Role Separation (enforced by OrderPolicy):
 *   CUSTOMERS (role=client) — place orders via the /shop portal only
 *   ADMIN / PROJECT_MANAGER — update fulfillment & payment status, delete
 *   TEAM_MEMBER             — view-only access to order list & details
 *
 * Routes:
 *   GET    /orders                        → index          [staff only]
 *   GET    /orders/{order}                → show           [staff or own]
 *   DELETE /orders/{order}                → destroy        [admin only]
 *   PATCH  /orders/{order}/status         → updateStatus   [admin, PM]
 *
 * Note: /orders/create and POST /orders are intentionally removed from
 * the admin area. Customers create orders exclusively at /shop.
 */
class OrderController extends Controller
{
    // ──────────────────────────────────────────────
    // INDEX — List orders with filters
    // Accessible to all staff (admin, PM, team_member)
    // ──────────────────────────────────────────────

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['customer', 'processedBy'])
            ->withCount('items');

        // Filter by order type (wholesale / retail)
        if ($request->filled('type')) {
            $query->where('order_type', $request->type);
        }

        // Filter by fulfillment status
        if ($request->filled('status')) {
            $query->where('fulfillment_status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment')) {
            $query->where('payment_status', $request->payment);
        }

        // Search by order number or customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest('ordered_at')->paginate(20)->withQueryString();

        // Summary counts for the filter bar
        $statusCounts = Order::selectRaw("fulfillment_status, COUNT(*) as total")
            ->groupBy('fulfillment_status')
            ->pluck('total', 'fulfillment_status');

        return view('orders.index', compact('orders', 'statusCounts'));
    }

    // ──────────────────────────────────────────────
    // CREATE — Show new order form
    // ──────────────────────────────────────────────

    public function create(): View
    {
        // Load all in-stock products grouped by category for the order form
        $products = Product::with('category')
            ->active()
            ->inStock()
            ->orderBy('name')
            ->get();

        $productsByCategory = $products->groupBy(fn ($p) => $p->category->name ?? 'Uncategorised');

        return view('orders.create', compact('productsByCategory'));
    }

    // ──────────────────────────────────────────────
    // STORE — Create order + items + deduct stock
    //         Wrapped in DB::transaction for safety
    // ──────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        // ── 1. Validate the order header ─────────────────────────────
        $request->validate([
            'order_type'         => ['required', 'in:wholesale,retail'],
            'customer_name'      => ['required', 'string', 'max:150'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'discount_amount'    => ['nullable', 'numeric', 'min:0'],

            // Line items array
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $orderType = $request->order_type;

        // ── 2. Pre-validate stock before opening transaction ──────────
        $lineItems = [];
        foreach ($request->items as $line) {
            $product = Product::findOrFail($line['product_id']);

            if ($product->stock_qty < $line['quantity']) {
                return back()
                    ->withInput()
                    ->with('error',
                        "Insufficient stock for \"{$product->name}\" " .
                        "(requested: {$line['quantity']}, available: {$product->stock_qty})."
                    );
            }

            $lineItems[] = [
                'product'    => $product,
                'quantity'   => (int) $line['quantity'],
                'unit_price' => $product->getPriceFor($orderType),
                'line_total' => round($product->getPriceFor($orderType) * $line['quantity'], 2),
            ];
        }

        // ── 3. Execute atomically ─────────────────────────────────────
        $order = DB::transaction(function () use ($request, $orderType, $lineItems) {

            // Create order header
            $order = Order::create([
                'order_number'       => Order::generateOrderNumber(),
                'customer_id'        => auth()->id(), // logged-in staff as proxy; extend for real customers
                'processed_by'       => auth()->id(),
                'order_type'         => $orderType,
                'fulfillment_status' => 'pending',
                'payment_status'     => 'unpaid',
                'subtotal'           => 0,
                'discount_amount'    => $request->input('discount_amount', 0),
                'total_amount'       => 0,
                'notes'              => $request->notes,
                'ordered_at'         => now(),
            ]);

            // Create line items + deduct stock
            foreach ($lineItems as $line) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $line['product']->id,
                    'quantity'   => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);

                // Deduct stock (also auto-updates stock_status)
                $line['product']->deductStock($line['quantity']);
            }

            // Recalculate subtotal and total_amount from saved items
            $order->recalculateTotals();

            return $order;
        });

        // Fire notifications outside the transaction (non-critical)
        $this->fireOrderPlacedNotifications($order->load('customer', 'items.product'));

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Order {$order->order_number} created. Total: ₱" . number_format($order->total_amount, 2));
    }

    // ── Fire notifications after transaction ──────────────────────────
    private function fireOrderPlacedNotifications(Order $order): void
    {
        // 1. Notify the customer
        $order->customer->notify(new OrderStatusNotification($order, 'placed'));

        // 2. Notify all admins + project managers
        $staff = User::whereIn('role', ['admin', 'project_manager'])->get();
        Notification::send($staff, new OrderStatusNotification($order, 'placed'));

        // 3. Check if any ordered products are now low/out-of-stock
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product->stock_qty <= $product->reorder_level) {
                Notification::send($staff, new LowStockNotification($product));
            }
        }
    }

    // ──────────────────────────────────────────────
    // SHOW — Single order detail
    // Staff can view any; customers only their own
    // ──────────────────────────────────────────────

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'customer',
            'processedBy',
            'items.product.category',
        ]);

        return view('orders.show', compact('order'));
    }

    // ──────────────────────────────────────────────
    // UPDATE STATUS — Toggle fulfillment + payment
    // PATCH /orders/{order}/status
    // ──────────────────────────────────────────────

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);  // admin / project_manager only

        $validated = $request->validate([
            'fulfillment_status' => ['sometimes', 'in:pending,processing,shipped,delivered,cancelled'],
            'payment_status'     => ['sometimes', 'in:unpaid,partial,paid'],
        ]);

        // Guard: cannot un-cancel a cancelled order
        if ($order->fulfillment_status === 'cancelled' && isset($validated['fulfillment_status'])) {
            return back()->with('error', 'Cancelled orders cannot be re-opened. Create a new order.');
        }

        // Guard: cancelling a non-delivered order restores stock
        if (
            isset($validated['fulfillment_status']) &&
            $validated['fulfillment_status'] === 'cancelled' &&
            $order->fulfillment_status !== 'delivered'
        ) {
            DB::transaction(function () use ($order, $validated) {
                foreach ($order->items as $item) {
                    $item->product->restockBy($item->quantity);
                }
                $order->update($validated);
            });

            // Notify customer their order was cancelled
            $order->customer->notify(
                new OrderStatusNotification($order, 'cancelled', auth()->user()->name)
            );

            return back()->with('success', "Order {$order->order_number} cancelled. Stock has been restored.");
        }

        $order->update($validated);

        // Notify the customer of the status change (non-cancellation)
        if (isset($validated['fulfillment_status'])) {
            $order->load('customer');
            $order->customer->notify(
                new OrderStatusNotification($order, $validated['fulfillment_status'], auth()->user()->name)
            );
        }

        $statusLabel = collect($validated)->map(fn ($v) => strtoupper(str_replace('_', ' ', $v)))->implode(' / ');

        return back()->with('success', "Order {$order->order_number} status updated to: {$statusLabel}.");
    }

    // ──────────────────────────────────────────────
    // DESTROY — Delete pending/cancelled orders only
    // ──────────────────────────────────────────────

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);  // admin only

        // Only allow deletion of pending or cancelled orders
        if (! in_array($order->fulfillment_status, ['pending', 'cancelled'])) {
            return back()->with(
                'error',
                "Order {$order->order_number} cannot be deleted — it is {$order->fulfillment_status}. " .
                "Only pending or cancelled orders can be removed."
            );
        }

        $number = $order->order_number;

        // Restore stock if pending (items were deducted on store)
        if ($order->fulfillment_status === 'pending') {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $item->product->restockBy($item->quantity);
                }
                $order->delete(); // cascades to order_items via DB constraint
            });
        } else {
            $order->delete();
        }

        return redirect()
            ->route('orders.index')
            ->with('success', "Order {$number} deleted.");
    }
}
