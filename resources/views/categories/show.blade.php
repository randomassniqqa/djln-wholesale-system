@extends('layouts.app')

@section('title', $category->name . ' — DJLN Marketing')
@section('page-title', $category->icon . ' ' . $category->name)

@section('content')
<div class="space-y-5">

    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="{{ route('categories.index') }}" class="hover:text-cyan-400 transition">Categories</a>
        <span>/</span><span class="text-gray-700">{{ $category->name }}</span>
    </nav>

    {{-- Header Card --}}
    <div class="grid-panel p-6 flex items-start justify-between gap-4" style="border-radius:6px;">
        <div class="space-y-2">
            <div class="flex items-center gap-3">
                <span class="text-3xl">{{ $category->icon ?? '📦' }}</span>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $category->name }}</h2>
                    <p class="metrics-display text-xs text-gray-500">{{ $category->slug }}</p>
                </div>
            </div>
            @if($category->description)
            <p class="text-sm text-gray-600 max-w-xl leading-relaxed">{{ $category->description }}</p>
            @endif
            <div class="flex items-center gap-3 pt-1">
                @if($category->is_active)
                    <span class="text-xs px-2 py-0.5" style="background:rgba(34,197,94,0.15); color:#22c55e; border:1px solid rgba(34,197,94,0.3); border-radius:4px;">Active</span>
                @else
                    <span class="text-xs px-2 py-0.5" style="background:rgba(107,114,128,0.15); color:#6b7280; border:1px solid rgba(107,114,128,0.3); border-radius:4px;">Inactive</span>
                @endif
                <span class="text-xs text-gray-600">Created {{ $category->created_at->format('M d, Y') }}</span>
            </div>
        </div>
        @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
        <div class="flex gap-2 shrink-0">
            <a href="{{ route('categories.edit', $category) }}"
               class="text-sm px-4 py-2"
               style="background:rgba(251,191,36,0.1); color:#fbbf24; border:1px solid rgba(251,191,36,0.3); border-radius:4px;">
                ✏️ Edit
            </a>
            <form method="POST" action="{{ route('categories.destroy', $category) }}"
                  onsubmit="return confirm('Delete {{ addslashes($category->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm px-4 py-2"
                        style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.3); border-radius:4px;">
                    🗑️ Delete
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="grid-panel p-4 text-center" style="border-radius:6px;">
            <p class="metrics-display text-2xl accent-cyan">{{ $products->total() }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Products</p>
        </div>
        <div class="grid-panel p-4 text-center" style="border-radius:6px;">
            <p class="metrics-display text-2xl text-green-400">{{ $products->where('stock_status', 'in_stock')->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">In Stock</p>
        </div>
        <div class="grid-panel p-4 text-center" style="border-radius:6px;">
            <p class="metrics-display text-2xl text-yellow-400">{{ $products->where('stock_status', 'low_stock')->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Low Stock</p>
        </div>
    </div>

    {{-- Products in this Category --}}
    <div class="grid-panel overflow-hidden" style="border-radius:6px;">
        <div class="px-5 py-3 flex items-center justify-between" style="border-bottom:1px solid #e5e7eb;">
            <p class="text-sm font-medium text-gray-700">Products in {{ $category->name }}</p>
            @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
            <a href="{{ route('products.create') }}"
               class="text-xs px-3 py-1"
               style="background:rgba(14,165,233,0.1); color:#0ea5e9; border:1px solid rgba(14,165,233,0.2); border-radius:4px;">
                + Add Product
            </a>
            @endif
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                    <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">SKU</th>
                    <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">Name</th>
                    <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Wholesale</th>
                    <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Retail</th>
                    <th class="text-center px-4 py-2 text-xs text-gray-500 uppercase">Stock</th>
                    <th class="text-center px-4 py-2 text-xs text-gray-500 uppercase">Status</th>
                    <th class="text-center px-4 py-2 text-xs text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                @php
                    $sc = match($product->stock_status) {
                        'in_stock'     => ['bg'=>'rgba(34,197,94,0.15)',   'text'=>'#22c55e', 'border'=>'rgba(34,197,94,0.3)'],
                        'low_stock'    => ['bg'=>'rgba(251,191,36,0.15)',  'text'=>'#fbbf24', 'border'=>'rgba(251,191,36,0.3)'],
                        'out_of_stock' => ['bg'=>'rgba(239,68,68,0.15)',   'text'=>'#ef4444', 'border'=>'rgba(239,68,68,0.3)'],
                        default        => ['bg'=>'rgba(107,114,128,0.15)', 'text'=>'#6b7280', 'border'=>'rgba(107,114,128,0.3)'],
                    };
                @endphp
                <tr style="border-bottom:1px solid #e5e7eb;" class="hover:bg-[#f9fafb] transition">
                    <td class="px-4 py-3 metrics-display text-xs text-gray-600">{{ $product->sku }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('products.show', $product) }}" class="text-gray-900 hover:text-cyan-400 transition font-medium">
                            {{ $product->name }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-right metrics-display text-gray-700 text-xs">₱{{ number_format($product->wholesale_price, 2) }}</td>
                    <td class="px-4 py-3 text-right metrics-display accent-cyan text-xs">₱{{ number_format($product->retail_price, 2) }}</td>
                    <td class="px-4 py-3 text-center metrics-display text-sm {{ $product->stock_qty <= $product->reorder_level ? 'text-yellow-400' : 'text-gray-900' }}">
                        {{ number_format($product->stock_qty) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-xs px-2 py-0.5"
                              style="background:{{ $sc['bg'] }}; color:{{ $sc['text'] }}; border:1px solid {{ $sc['border'] }}; border-radius:4px;">
                            {{ ucwords(str_replace('_',' ', $product->stock_status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('products.show', $product) }}" class="text-xs px-2 py-1"
                           style="background:rgba(14,165,233,0.1); color:#0ea5e9; border:1px solid rgba(14,165,233,0.2); border-radius:4px;">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-xs text-gray-500">
                        No products in this category yet.
                        <a href="{{ route('products.create') }}" class="accent-cyan ml-1 hover:underline">Add one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())
        <div class="px-4 py-3" style="border-top:1px solid #e5e7eb;">
            {{ $products->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

