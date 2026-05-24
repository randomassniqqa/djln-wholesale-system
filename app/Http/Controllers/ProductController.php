<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * DJLN Marketing — ProductController
 *
 * Manages individual inventory SKUs (Gummy Bears, Latex Balloons, etc.)
 * Replaces the old TaskController logic.
 *
 * Routes:
 *   GET    /products                      → index
 *   GET    /products/{product}            → show
 *   GET    /products/create               → create   [admin, project_manager]
 *   POST   /products                      → store    [admin, project_manager]
 *   GET    /products/{product}/edit       → edit     [admin, project_manager]
 *   PUT    /products/{product}            → update   [admin, project_manager]
 *   DELETE /products/{product}            → destroy  [admin, project_manager]
 *   PATCH  /products/{product}/restock    → restock  [admin, project_manager]
 */
class ProductController extends Controller
{
    // ──────────────────────────────────────────────
    // INDEX — List all products with filters
    // ──────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = Product::with('category')->active();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // Search by name or SKU
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $products   = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = Category::active()->orderBy('name')->get(['id', 'name', 'icon']);

        return view('products.index', compact('products', 'categories'));
    }

    // ──────────────────────────────────────────────
    // CREATE — Show form for new product
    // ──────────────────────────────────────────────

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name', 'icon']);

        return view('products.create', compact('categories'));
    }

    // ──────────────────────────────────────────────
    // STORE — Persist new product
    // ──────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id'     => ['required', 'exists:product_categories,id'],
            'name'            => ['required', 'string', 'max:150'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'brand_name'      => ['nullable', 'string', 'max:100'],
            'weight_volume'   => ['nullable', 'string', 'max:50'],
            'unit'            => ['required', 'string', 'in:piece,kg,box,pack,set,bag'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'retail_price'    => ['required', 'numeric', 'min:0', 'gte:wholesale_price'],
            'stock_qty'       => ['required', 'integer', 'min:0'],
            'reorder_level'   => ['required', 'integer', 'min:0'],
            'moq'             => ['nullable', 'integer', 'min:1'],
            'expiry_date'     => ['nullable', 'date', 'after:today'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'is_active'       => ['boolean'],
        ]);

        $validated['created_by']   = auth()->id();
        $validated['is_active']    = $request->boolean('is_active', true);
        $validated['moq']          = $request->input('moq', 1);

        // Auto-generate SKU: [CATEGORY]-[YEAR]-[INCREMENTING NUMBER]
        $category = Category::find($validated['category_id']);
        $catPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category->name), 0, 5));
        if (strlen($catPrefix) === 0) $catPrefix = 'PRD';
        $year = date('Y');
        
        $lastProduct = Product::where('sku', 'like', "{$catPrefix}-{$year}-%")->orderBy('sku', 'desc')->first();
        if ($lastProduct) {
            $lastNum = intval(substr($lastProduct->sku, strrpos($lastProduct->sku, '-') + 1));
            $nextNum = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '001';
        }
        $validated['sku'] = "{$catPrefix}-{$year}-{$nextNum}";

        // Auto-resolve stock_status from qty
        $product = new Product($validated);
        $validated['stock_status'] = $product->resolveStockStatus($validated['stock_qty']);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('success', "Product \"{$product->name}\" [{$product->sku}] added to inventory.");
    }

    // ──────────────────────────────────────────────
    // SHOW — Single product detail page
    // ──────────────────────────────────────────────

    public function show(Product $product): View
    {
        $product->load('category');

        // Recent order items for this product (last 10)
        $recentSales = $product->orderItems()
            ->with('order')
            ->latest()
            ->limit(10)
            ->get();

        return view('products.show', compact('product', 'recentSales'));
    }

    // ──────────────────────────────────────────────
    // EDIT — Show edit form
    // ──────────────────────────────────────────────

    public function edit(Product $product): View
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name', 'icon']);

        return view('products.edit', compact('product', 'categories'));
    }

    // ──────────────────────────────────────────────
    // UPDATE — Persist edits
    // ──────────────────────────────────────────────

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'category_id'     => ['required', 'exists:product_categories,id'],
            'name'            => ['required', 'string', 'max:150'],
            'sku'             => ['required', 'string', 'max:50', 'unique:inventory_items,sku,' . $product->id],
            'description'     => ['nullable', 'string', 'max:1000'],
            'brand_name'      => ['nullable', 'string', 'max:100'],
            'weight_volume'   => ['nullable', 'string', 'max:50'],
            'unit'            => ['required', 'string', 'in:piece,kg,box,pack,set,bag'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'retail_price'    => ['required', 'numeric', 'min:0', 'gte:wholesale_price'],
            'stock_qty'       => ['required', 'integer', 'min:0'],
            'reorder_level'   => ['required', 'integer', 'min:0'],
            'moq'             => ['nullable', 'integer', 'min:1'],
            'expiry_date'     => ['nullable', 'date'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'is_active'       => ['boolean'],
        ]);

        $validated['is_active']    = $request->boolean('is_active', true);
        $validated['moq']          = $request->input('moq', 1);
        $validated['stock_status'] = $product->resolveStockStatus($validated['stock_qty']);

        if ($request->hasFile('image')) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('success', "Product \"{$product->name}\" updated.");
    }

    // ──────────────────────────────────────────────
    // DESTROY — Delete product
    // ──────────────────────────────────────────────

    public function destroy(Product $product): RedirectResponse
    {
        // Safety: block deletion if product has order history
        if ($product->orderItems()->exists()) {
            return back()->with(
                'error',
                "Cannot delete \"{$product->name}\" — it appears in existing orders. " .
                "Set it to \"Discontinued\" instead."
            );
        }

        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', "Product \"{$name}\" removed from inventory.");
    }

    // ──────────────────────────────────────────────
    // RESTOCK — Add stock from a delivery/arrival
    // PATCH /products/{product}/restock
    // ──────────────────────────────────────────────

    public function restock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'qty_received' => ['required', 'integer', 'min:1', 'max:99999'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $before = $product->stock_qty;
        $product->restockBy($validated['qty_received']); // updates qty + stock_status + saves

        return back()->with(
            'success',
            "Restocked \"{$product->name}\": {$before} → {$product->stock_qty} units. " .
            "Status: " . strtoupper(str_replace('_', ' ', $product->stock_status)) . "."
        );
    }
}
