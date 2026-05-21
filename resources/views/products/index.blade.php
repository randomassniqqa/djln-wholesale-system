@extends('layouts.app')

@section('title', 'Products — DJLN Marketing')
@section('page-title', '🛒 Product Catalogue')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;" x-data="productPage()">

    {{-- ── FILTER + TOGGLE BAR ──────────────────────────────────── --}}
    <form method="GET" action="{{ route('products.index') }}" class="card" style="padding:16px;display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or SKU..." class="form-input">
        </div>

        <div style="min-width:150px;">
            <label class="form-label">Category</label>
            <select name="category" class="form-input">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->icon }} {{ $cat->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div style="min-width:140px;">
            <label class="form-label">Stock Status</label>
            <select name="stock_status" class="form-input">
                <option value="">All Statuses</option>
                <option value="in_stock"     {{ request('stock_status') === 'in_stock'     ? 'selected':'' }}>✅ In Stock</option>
                <option value="low_stock"    {{ request('stock_status') === 'low_stock'    ? 'selected':'' }}>⚠️ Low Stock</option>
                <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected':'' }}>❌ Out of Stock</option>
                <option value="discontinued" {{ request('stock_status') === 'discontinued' ? 'selected':'' }}>⛔ Discontinued</option>
            </select>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('products.index') }}" class="btn btn-ghost">Clear</a>
        </div>

        {{-- View toggle --}}
        <div style="margin-left:auto;display:flex;gap:4px;background:var(--surface2);padding:4px;border-radius:8px;border:1px solid var(--border);">
            <button type="button" @click="view='table'" :style="view==='table' ? 'background:var(--surface);box-shadow:0 1px 2px rgba(0,0,0,0.05);color:var(--cyan);' : 'color:var(--muted);'" style="padding:4px 10px;font-size:12px;border-radius:4px;font-weight:600;border:none;cursor:pointer;">
                ☰ Table
            </button>
            <button type="button" @click="view='grid'" :style="view==='grid' ? 'background:var(--surface);box-shadow:0 1px 2px rgba(0,0,0,0.05);color:var(--cyan);' : 'color:var(--muted);'" style="padding:4px 10px;font-size:12px;border-radius:4px;font-weight:600;border:none;cursor:pointer;">
                ⊞ Grid
            </button>
        </div>

        @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
        <a href="{{ route('products.create') }}" class="btn btn-primary" style="background:var(--purple);border-color:var(--purple);">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </a>
        @endif
    </form>

    <p style="font-size:12px;color:var(--muted);">
        Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
    </p>

    {{-- ── TABLE VIEW ───────────────────────────────────────────── --}}
    <div x-show="view === 'table'" x-transition>
        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th style="text-align:right;">Wholesale</th>
                        <th style="text-align:right;">Retail</th>
                        <th style="text-align:center;">Margin</th>
                        <th style="text-align:center;">Stock</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    @php
                        $sc = match($product->stock_status) {
                            'in_stock'     => ['badge'=>'badge-green', 'bar'=>'var(--green)'],
                            'low_stock'    => ['badge'=>'badge-amber', 'bar'=>'var(--amber)'],
                            'out_of_stock' => ['badge'=>'badge-red',   'bar'=>'var(--red)'],
                            default        => ['badge'=>'badge-muted', 'bar'=>'var(--muted)'],
                        };
                        $margin = $product->wholesale_price > 0 ? round((($product->retail_price - $product->wholesale_price) / $product->retail_price) * 100) : 0;
                        $marginColor = $margin >= 30 ? 'var(--green)' : ($margin >= 15 ? 'var(--amber)' : 'var(--red)');
                        $stockPct = $product->reorder_level > 0 ? min(100, round(($product->stock_qty / ($product->reorder_level * 2)) * 100)) : 100;
                        
                        $catSlug = strtolower(str_replace([' ','&','/'],'-', $product->category->name ?? ''));
                        $catClass = match(true) {
                            str_contains($catSlug,'cand')||str_contains($catSlug,'gumm') => 'cat-candy',
                            str_contains($catSlug,'loll') => 'cat-lolly',
                            str_contains($catSlug,'choc') => 'cat-choco',
                            str_contains($catSlug,'ball') => 'cat-balloon',
                            str_contains($catSlug,'part') => 'cat-party',
                            default => 'cat-default',
                        };
                    @endphp
                    <tr>
                        <td class="mono" style="font-size:11px;color:var(--muted);">{{ $product->sku }}</td>
                        <td>
                            <a href="{{ route('products.show', $product) }}" style="font-size:13px;font-weight:600;color:var(--text);text-decoration:none;">{{ $product->name }}</a>
                            <p style="font-size:11px;color:var(--muted);">per {{ $product->unit }}</p>
                        </td>
                        <td>
                            <span class="badge {{ $catClass }}">{{ $product->category->icon ?? '' }} {{ $product->category->name ?? '—' }}</span>
                        </td>
                        <td class="mono" style="text-align:right;font-size:12px;color:var(--muted);">₱{{ number_format($product->wholesale_price, 2) }}</td>
                        <td class="mono" style="text-align:right;font-size:13px;font-weight:700;color:var(--cyan);">₱{{ number_format($product->retail_price, 2) }}</td>
                        <td style="text-align:center;">
                            <span class="margin-pill" style="color:{{ $marginColor }};border-color:{{ $marginColor }};background:transparent;">{{ $margin }}%</span>
                        </td>
                        <td style="min-width:90px;">
                            <p class="mono" style="font-size:12px;text-align:center;margin-bottom:4px;color:{{ $product->stock_qty <= $product->reorder_level ? 'var(--amber)' : 'var(--text)' }};">
                                {{ number_format($product->stock_qty) }}
                            </p>
                            <div class="stock-track">
                                <div class="stock-fill" style="width:{{ $stockPct }}%;background:{{ $sc['bar'] }};"></div>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $sc['badge'] }}">{{ ucwords(str_replace('_',' ', $product->stock_status)) }}</span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-ghost" style="padding:4px 8px;font-size:11px;">View</a>
                                @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
                                <button type="button" @click="openQuickEdit({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_qty }}, {{ $product->reorder_level }})" class="btn" style="padding:4px 8px;font-size:11px;background:#fffbeb;color:#d97706;border-color:#fde68a;">
                                    Restock
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;color:var(--muted);">
                            <p style="font-size:32px;">🛒</p>
                            <p style="font-size:13px;margin-top:8px;">No products match your filters.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── GRID VIEW ────────────────────────────────────────────── --}}
    <div x-show="view === 'grid'" x-transition>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
            @forelse($products as $product)
            @php
                $margin = $product->wholesale_price > 0 ? round((($product->retail_price - $product->wholesale_price) / $product->retail_price) * 100) : 0;
                $marginColor = $margin >= 30 ? 'var(--green)' : ($margin >= 15 ? 'var(--amber)' : 'var(--red)');
                $sc = match($product->stock_status) {
                    'in_stock'  => ['text'=>'var(--green)','bar'=>'var(--green)'],
                    'low_stock' => ['text'=>'var(--amber)','bar'=>'var(--amber)'],
                    default     => ['text'=>'var(--red)',  'bar'=>'var(--red)'],
                };
                $stockPct = $product->reorder_level > 0 ? min(100, round(($product->stock_qty / ($product->reorder_level * 2)) * 100)) : 100;
            @endphp
            <div class="card" style="display:flex;flex-direction:column;overflow:hidden;">
                <a href="{{ route('products.show', $product) }}" style="height:120px;background:var(--surface2);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:40px;text-decoration:none;">
                    @if($product->image_url)
                        <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}" style="max-height:100%;max-width:100%;object-fit:contain;padding:8px;">
                    @else
                        {{ $product->category->icon ?? '📦' }}
                    @endif
                </a>
                <div style="padding:14px;display:flex;flex-direction:column;flex:1;">
                    <p class="mono" style="font-size:10px;color:var(--muted);margin-bottom:4px;">{{ $product->sku }}</p>
                    <a href="{{ route('products.show', $product) }}" style="font-size:13px;font-weight:600;color:var(--text);text-decoration:none;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4;">
                        {{ $product->name }}
                    </a>
                    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-top:12px;margin-bottom:12px;">
                        <div>
                            <p style="font-size:10px;color:var(--muted);text-decoration:line-through;">₱{{ number_format($product->wholesale_price, 2) }}</p>
                            <p class="mono" style="font-size:16px;font-weight:700;color:var(--cyan);">₱{{ number_format($product->retail_price, 2) }}</p>
                        </div>
                        <span class="margin-pill" style="color:{{ $marginColor }};border-color:{{ $marginColor }};background:transparent;">{{ $margin }}%</span>
                    </div>
                    <div style="margin-top:auto;">
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;">
                            <span class="mono" style="font-weight:700;color:{{ $sc['text'] }};">{{ number_format($product->stock_qty) }} {{ $product->unit }}</span>
                            <span style="color:var(--muted);">min {{ $product->reorder_level }}</span>
                        </div>
                        <div class="stock-track">
                            <div class="stock-fill" style="width:{{ $stockPct }}%;background:{{ $sc['bar'] }};"></div>
                        </div>
                        @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
                        <button type="button" @click="openQuickEdit({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_qty }}, {{ $product->reorder_level }})" class="btn btn-ghost" style="width:100%;margin-top:10px;justify-content:center;font-size:11px;padding:6px;">
                            + Restock
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--muted);">
                <p style="font-size:32px;">🛒</p>
                <p style="font-size:13px;margin-top:8px;">No products found.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    <div>{{ $products->links() }}</div>
    @endif

</div>

{{-- ══ QUICK RESTOCK MODAL ══ --}}
<div id="qe-modal" x-data="{ show: false }" x-show="show" x-cloak @keydown.escape.window="show = false" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);">
    <div class="card" style="padding:24px;width:100%;max-width:360px;display:flex;flex-direction:column;gap:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:16px;font-weight:700;color:var(--text);">⚡ Quick Restock</p>
            <button @click="show = false" style="background:none;border:none;font-size:20px;color:var(--muted);cursor:pointer;">&times;</button>
        </div>
        <p style="font-size:13px;color:var(--text2);" id="qe-name">—</p>
        <p style="font-size:12px;color:var(--muted);">
            Stock: <span id="qe-stock" class="mono text-text"></span>
            · Reorder: <span id="qe-reorder" class="mono text-amber"></span>
        </p>
        <form id="qe-form" method="POST" style="display:flex;flex-direction:column;gap:12px;">
            @csrf @method('PATCH')
            <div>
                <label class="form-label">Quantity to Add <span style="color:var(--red);">*</span></label>
                <input type="number" name="qty_received" id="qe-qty" min="1" required class="form-input" placeholder="e.g. 50">
            </div>
            <div>
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-input" placeholder="PO#, Supplier...">
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" class="btn" style="flex:1;justify-content:center;background:#f0fdf4;color:var(--green);border-color:#bbf7d0;">✓ Confirm Restock</button>
                <button type="button" @click="show = false" class="btn btn-ghost">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function productPage() {
    return {
        view: localStorage.getItem('djln_product_view') || 'table',
        init() {
            this.$watch('view', v => localStorage.setItem('djln_product_view', v));
        },
        openQuickEdit(id, name, stock, reorder) {
            document.getElementById('qe-form').action = '/products/' + id + '/restock';
            document.getElementById('qe-name').textContent    = name;
            document.getElementById('qe-stock').textContent   = stock;
            document.getElementById('qe-reorder').textContent = reorder;
            document.getElementById('qe-qty').value           = '';
            this.$dispatch('open-qe');
        }
    };
}
document.addEventListener('alpine:init', () => { Alpine.store('qeModal', { show: false }); });
document.addEventListener('open-qe', () => {
    const modal = document.getElementById('qe-modal');
    if (modal && modal._x_dataStack) { modal._x_dataStack[0].show = true; } 
    else { modal.style.display = 'flex'; }
});
</script>
@endsection

