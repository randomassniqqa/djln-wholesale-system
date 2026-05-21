@extends('layouts.app')

@section('title', $product->name . ' — DJLN Marketing')
@section('page-title', '🛒 ' . $product->name)

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="{{ route('products.index') }}" class="hover:text-cyan-400 transition">Products</a>
        <span>/</span>
        <span class="text-gray-700">{{ $product->sku }}</span>
    </nav>

    {{-- Top Row: Product Header + Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Product Info Card --}}
        <div class="lg:col-span-2 grid-panel p-6 space-y-4" style="border-radius:6px;">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        @if($product->image_url)
                            <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                        @else
                            <span class="text-2xl">{{ $product->category->icon ?? '📦' }}</span>
                        @endif
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h2>
                            <p class="text-xs text-gray-500">
                                <a href="{{ route('categories.show', $product->category) }}" class="hover:text-cyan-400 transition">
                                    {{ $product->category->name ?? '—' }}
                                </a>
                            </p>
                        </div>
                    </div>
                    <p class="metrics-display text-xs text-gray-500">SKU: {{ $product->sku }}</p>
                </div>

                {{-- Status Badge --}}
                @php
                    $sc = match($product->stock_status) {
                        'in_stock'     => ['bg'=>'rgba(34,197,94,0.15)',   'text'=>'#22c55e', 'border'=>'rgba(34,197,94,0.3)'],
                        'low_stock'    => ['bg'=>'rgba(251,191,36,0.15)',  'text'=>'#fbbf24', 'border'=>'rgba(251,191,36,0.3)'],
                        'out_of_stock' => ['bg'=>'rgba(239,68,68,0.15)',   'text'=>'#ef4444', 'border'=>'rgba(239,68,68,0.3)'],
                        default        => ['bg'=>'rgba(107,114,128,0.15)', 'text'=>'#6b7280', 'border'=>'rgba(107,114,128,0.3)'],
                    };
                @endphp
                <span class="text-xs px-3 py-1 font-medium shrink-0"
                      style="background:{{ $sc['bg'] }}; color:{{ $sc['text'] }}; border:1px solid {{ $sc['border'] }}; border-radius:4px;">
                    {{ ucwords(str_replace('_', ' ', $product->stock_status)) }}
                </span>
            </div>

            @if($product->description)
            <p class="text-sm text-gray-600 leading-relaxed">{{ $product->description }}</p>
            @endif

            {{-- Spec Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-2" style="border-top:1px solid #e5e7eb;">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Unit</p>
                    <p class="text-sm text-gray-900 capitalize">{{ $product->unit }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Reorder Level</p>
                    <p class="metrics-display text-sm text-gray-900">{{ $product->reorder_level }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Expiry Date</p>
                    <p class="text-sm {{ $product->expiry_date && $product->expiry_date->isPast() ? 'text-red-400' : 'text-gray-900' }}">
                        {{ $product->expiry_date ? $product->expiry_date->format('M d, Y') : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Active</p>
                    <p class="text-sm {{ $product->is_active ? 'text-green-400' : 'text-gray-500' }}">
                        {{ $product->is_active ? 'Yes' : 'No' }}
                    </p>
                </div>
            </div>

            {{-- Admin Actions --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
            <div class="flex gap-3 pt-2" style="border-top:1px solid #e5e7eb;">
                <a href="{{ route('products.edit', $product) }}"
                   class="text-sm px-4 py-2 transition"
                   style="background:rgba(251,191,36,0.1); color:#fbbf24; border:1px solid rgba(251,191,36,0.3); border-radius:4px;">
                    ✏️ Edit Product
                </a>
                <form method="POST" action="{{ route('products.destroy', $product) }}"
                      onsubmit="return confirm('Delete {{ addslashes($product->name) }}? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm px-4 py-2 transition"
                            style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.3); border-radius:4px;">
                        🗑️ Delete
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Right Column: Pricing + Stock + Restock --}}
        <div class="space-y-4">

            {{-- Pricing Card --}}
            <div class="grid-panel p-5 space-y-3" style="border-radius:6px;">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Pricing</p>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Wholesale</p>
                        <p class="metrics-display text-xl text-gray-700">₱{{ number_format($product->wholesale_price, 2) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 mb-1">Retail</p>
                        <p class="metrics-display text-xl accent-cyan">₱{{ number_format($product->retail_price, 2) }}</p>
                    </div>
                </div>
                <div class="pt-2 flex justify-between text-xs text-gray-500" style="border-top:1px solid #e5e7eb;">
                    <span>Margin</span>
                    <span class="text-green-400 font-medium">{{ $product->margin_percent }}%</span>
                </div>
            </div>

            {{-- Stock Card --}}
            <div class="grid-panel p-5 space-y-3" style="border-radius:6px;">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Stock Level</p>
                <p class="metrics-display text-4xl {{ $product->stock_qty <= $product->reorder_level ? 'text-yellow-400' : 'text-gray-900' }}">
                    {{ number_format($product->stock_qty) }}
                </p>
                <p class="text-xs text-gray-500">units in stock · reorder at {{ $product->reorder_level }}</p>
                @if($product->needs_reorder)
                <div class="text-xs px-3 py-2" style="background:rgba(251,191,36,0.1); border:1px solid rgba(251,191,36,0.3); border-radius:4px; color:#fbbf24;">
                    ⚠️ Below reorder level — place a purchase order.
                </div>
                @endif
            </div>

            {{-- Restock Form --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
            <div class="grid-panel p-5 space-y-3" style="border-radius:6px;">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Restock Arrival</p>
                <form method="POST" action="{{ route('products.restock', $product) }}">
                    @csrf @method('PATCH')
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Quantity Received</label>
                            <input type="number" name="qty_received" min="1" required
                                   class="w-full px-3 py-2 text-sm text-gray-900 metrics-display"
                                   style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                                   placeholder="e.g. 50">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Notes (optional)</label>
                            <input type="text" name="notes"
                                   class="w-full px-3 py-2 text-sm text-gray-900"
                                   style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                                   placeholder="Supplier, PO#, etc.">
                        </div>
                        <button type="submit" class="w-full py-2 text-sm font-medium transition"
                                style="background:rgba(14,165,233,0.15); border:1px solid rgba(14,165,233,0.4); color:#0ea5e9; border-radius:4px;">
                            + Add Stock
                        </button>
                    </div>
                </form>
            </div>
            @endif

        </div>
    </div>

    {{-- Recent Sales History --}}
    <div class="grid-panel overflow-hidden" style="border-radius:6px;">
        <div class="px-5 py-3" style="border-bottom:1px solid #e5e7eb;">
            <p class="text-sm font-medium text-gray-700">Recent Sales (last 10 order lines)</p>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                    <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">Order #</th>
                    <th class="text-center px-4 py-2 text-xs text-gray-500 uppercase">Type</th>
                    <th class="text-center px-4 py-2 text-xs text-gray-500 uppercase">Qty</th>
                    <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Unit Price</th>
                    <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Line Total</th>
                    <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSales as $item)
                <tr style="border-bottom:1px solid #e5e7eb;" class="hover:bg-[#f9fafb] transition">
                    <td class="px-4 py-2">
                        <a href="{{ route('orders.show', $item->order) }}" class="metrics-display text-xs accent-cyan hover:underline">
                            {{ $item->order->order_number }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <span class="text-xs" style="color:{{ $item->order->order_type === 'wholesale' ? '#a855f7' : '#0ea5e9' }};">
                            {{ ucfirst($item->order->order_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-center metrics-display text-gray-700">{{ $item->quantity }}</td>
                    <td class="px-4 py-2 text-right metrics-display text-gray-700">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-4 py-2 text-right metrics-display text-gray-900">₱{{ number_format($item->line_total, 2) }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">
                        {{ $item->order->ordered_at ? $item->order->ordered_at->format('M d, Y') : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-xs text-gray-500">No sales recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

