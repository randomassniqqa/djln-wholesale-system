@extends('layouts.shop')
@section('title', 'Shop Catalog — DJLN Wholesale')

@section('content')

{{-- ══════ PAGE HEADER ══════ --}}
<div style="margin-bottom:24px;">
    <h1 style="font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-0.02em;margin-bottom:4px;">
        🛒 Product Catalog
    </h1>
    <p style="color:#94a3b8;font-size:14px;">Browse our full wholesale range and add items to your cart.</p>
</div>

{{-- ══════ SEARCH + CATEGORY FILTERS ══════ --}}
<form method="GET" action="{{ route('customer.catalog') }}">
    <div class="search-bar">
        <input type="text" name="search" class="search-input"
               placeholder="Search products by name or SKU…"
               value="{{ request('search') }}">
        @if(request('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        <button type="submit" class="search-btn">🔍 Search</button>
        @if(request()->hasAny(['search','category']))
            <a href="{{ route('customer.catalog') }}" class="btn-shop-secondary">Clear</a>
        @endif
    </div>
</form>

{{-- Category pills --}}
<div class="cat-pills">
    <a href="{{ route('customer.catalog', array_filter(['search' => request('search')])) }}"
       class="cat-pill {{ !request('category') ? 'active' : '' }}">
        🏪 All Products
        <span style="background:rgba(255,255,255,0.3);padding:1px 7px;border-radius:99px;font-size:10px;font-weight:700;">
            {{ $products->count() }}
        </span>
    </a>
    @foreach($categories as $cat)
        <a href="{{ route('customer.catalog', array_filter(['category' => $cat->id, 'search' => request('search')])) }}"
           class="cat-pill {{ request('category') == $cat->id ? 'active' : '' }}">
            {{ $cat->icon ?? '📦' }} {{ $cat->name }}
        </a>
    @endforeach
</div>

{{-- Active category header --}}
@if($activeCategory)
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;padding:12px 16px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;">
        <span style="font-size:20px;">{{ $activeCategory->icon ?? '📦' }}</span>
        <div>
            <div style="font-weight:700;color:#0369a1;">{{ $activeCategory->name }}</div>
            <div style="font-size:11px;color:#7dd3fc;">Showing {{ $products->count() }} product(s) in this category</div>
        </div>
    </div>
@endif

{{-- ══════ PRODUCT GRID ══════ --}}
@if($products->isEmpty())
    <div style="text-align:center;padding:60px 20px;background:white;border-radius:16px;border:1px solid #e2e8f0;">
        <div style="font-size:52px;margin-bottom:16px;">🔍</div>
        <div style="font-weight:700;font-size:18px;color:#0f172a;margin-bottom:8px;">No products found</div>
        <div style="color:#94a3b8;font-size:14px;margin-bottom:20px;">Try a different category or search term.</div>
        <a href="{{ route('customer.catalog') }}" class="btn-shop-primary">View All Products</a>
    </div>
@else
    <div class="product-grid">
        @foreach($products as $product)
            <div class="product-card">
                {{-- Product Image / Icon --}}
                <div class="product-image">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                             style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <span style="font-size:52px;">{{ $product->category->icon ?? '📦' }}</span>
                    @endif
                </div>

                <div class="product-body">
                    {{-- Category tag --}}
                    <div class="product-category-tag">
                        {{ $product->category->name ?? 'General' }}
                    </div>

                    {{-- Name --}}
                    <div class="product-name">{{ $product->name }}</div>
                    <div class="product-unit">per {{ $product->unit }} · SKU: {{ $product->sku }}</div>

                    {{-- Wholesale price (prominent) --}}
                    <div class="product-price-row">
                        <span class="product-price">₱{{ number_format($product->wholesale_price, 2) }}</span>
                        <span class="product-price-label">wholesale</span>
                    </div>

                    {{-- MOQ hint --}}
                    <div class="product-moq">
                        📦 Min. order: {{ $product->reorder_level > 0 ? $product->reorder_level : 1 }} {{ $product->unit }}(s)
                    </div>

                    {{-- Stock indicator (NO raw numbers) --}}
                    <div>
                        @if($product->stock_status === 'out_of_stock')
                            <span class="stock-tag stock-out">❌ Out of Stock</span>
                        @else
                            <span class="stock-tag stock-available">✅ In Stock</span>
                        @endif
                    </div>

                    {{-- Add to cart form --}}
                    @if($product->stock_status !== 'out_of_stock')
                        <form method="POST" action="{{ route('customer.cart.add') }}"
                              style="display:flex;gap:8px;align-items:center;margin-top:12px;">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="number" name="quantity"
                                   class="qty-input"
                                   value="{{ $product->reorder_level > 0 ? $product->reorder_level : 1 }}"
                                   min="1" max="9999">
                            <button type="submit" class="product-add-btn" style="flex:1;">
                                🛒 Add to Cart
                            </button>
                        </form>
                    @else
                        <button class="product-add-btn" disabled style="margin-top:12px;">
                            Out of Stock
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
