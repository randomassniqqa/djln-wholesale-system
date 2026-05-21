@extends('layouts.app')

@section('title', $order->order_number . ' — DJLN Marketing')
@section('page-title', '📋 ' . $order->order_number)

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="{{ route('orders.index') }}" class="hover:text-cyan-400 transition">Orders</a>
        <span>/</span>
        <span class="text-gray-700">{{ $order->order_number }}</span>
    </nav>

    @php
        $fulfillColor = match($order->fulfillment_status) {
            'pending'    => ['bg'=>'rgba(251,191,36,0.15)',  'text'=>'#fbbf24', 'border'=>'rgba(251,191,36,0.3)'],
            'processing' => ['bg'=>'rgba(14,165,233,0.15)',  'text'=>'#0ea5e9', 'border'=>'rgba(14,165,233,0.3)'],
            'shipped'    => ['bg'=>'rgba(168,85,247,0.15)',  'text'=>'#a855f7', 'border'=>'rgba(168,85,247,0.3)'],
            'delivered'  => ['bg'=>'rgba(34,197,94,0.15)',   'text'=>'#22c55e', 'border'=>'rgba(34,197,94,0.3)'],
            'cancelled'  => ['bg'=>'rgba(239,68,68,0.15)',   'text'=>'#ef4444', 'border'=>'rgba(239,68,68,0.3)'],
            default      => ['bg'=>'rgba(107,114,128,0.15)', 'text'=>'#6b7280', 'border'=>'rgba(107,114,128,0.3)'],
        };
        $payColor = match($order->payment_status) {
            'paid'    => ['bg'=>'rgba(34,197,94,0.15)',   'text'=>'#22c55e', 'border'=>'rgba(34,197,94,0.3)'],
            'partial' => ['bg'=>'rgba(251,191,36,0.15)',  'text'=>'#fbbf24', 'border'=>'rgba(251,191,36,0.3)'],
            default   => ['bg'=>'rgba(239,68,68,0.15)',   'text'=>'#ef4444', 'border'=>'rgba(239,68,68,0.3)'],
        };
    @endphp

    {{-- Order Header Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Order Meta --}}
        <div class="lg:col-span-2 grid-panel p-6 space-y-4" style="border-radius:6px;">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="metrics-display text-lg font-bold accent-cyan">{{ $order->order_number }}</p>
                    <div class="flex flex-wrap gap-2">
                        {{-- Order type --}}
                        <span class="text-xs px-2 py-0.5"
                              style="background:{{ $order->order_type === 'wholesale' ? 'rgba(168,85,247,0.15)' : 'rgba(14,165,233,0.15)' }};
                                     color:{{ $order->order_type === 'wholesale' ? '#a855f7' : '#0ea5e9' }};
                                     border:1px solid {{ $order->order_type === 'wholesale' ? 'rgba(168,85,247,0.3)' : 'rgba(14,165,233,0.3)' }};
                                     border-radius:4px;">
                            {{ ucfirst($order->order_type) }}
                        </span>
                        {{-- Fulfillment --}}
                        <span class="text-xs px-2 py-0.5 font-medium"
                              style="background:{{ $fulfillColor['bg'] }}; color:{{ $fulfillColor['text'] }}; border:1px solid {{ $fulfillColor['border'] }}; border-radius:4px;">
                            {{ ucfirst($order->fulfillment_status) }}
                        </span>
                        {{-- Payment --}}
                        <span class="text-xs px-2 py-0.5 font-medium"
                              style="background:{{ $payColor['bg'] }}; color:{{ $payColor['text'] }}; border:1px solid {{ $payColor['border'] }}; border-radius:4px;">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Customer + Staff --}}
            <div class="grid grid-cols-2 gap-4 pt-3" style="border-top:1px solid #e5e7eb;">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Customer</p>
                    <p class="text-sm text-gray-900">{{ $order->customer->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Processed By</p>
                    <p class="text-sm text-gray-900">{{ $order->processedBy->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Order Date</p>
                    <p class="text-sm text-gray-900">{{ $order->ordered_at ? $order->ordered_at->format('M d, Y H:i') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Created</p>
                    <p class="text-sm text-gray-900">{{ $order->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>

            @if($order->notes)
            <div class="pt-3" style="border-top:1px solid #e5e7eb;">
                <p class="text-xs text-gray-500 mb-1">Notes</p>
                <p class="text-sm text-gray-700 leading-relaxed">{{ $order->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Right: Totals + Status Update --}}
        <div class="space-y-4">

            {{-- Totals Card --}}
            <div class="grid-panel p-5 space-y-3" style="border-radius:6px;">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Order Totals</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="metrics-display text-gray-700">₱{{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Discount</span>
                        <span class="metrics-display text-red-400">− ₱{{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between pt-2 font-semibold" style="border-top:1px solid #e5e7eb;">
                        <span class="text-gray-900">Total</span>
                        <span class="metrics-display text-xl accent-cyan">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Status Update Form --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
            @if($order->fulfillment_status !== 'cancelled')
            <div class="grid-panel p-5 space-y-3" style="border-radius:6px;">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Update Status</p>
                <form method="POST" action="{{ route('orders.update-status', $order) }}">
                    @csrf @method('PATCH')
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Fulfillment</label>
                            <select name="fulfillment_status" class="w-full px-3 py-2 text-sm text-gray-900"
                                    style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px;">
                                @foreach(['pending','processing','shipped','delivered','cancelled'] as $fs)
                                <option value="{{ $fs }}" {{ $order->fulfillment_status === $fs ? 'selected' : '' }}>
                                    {{ ucfirst($fs) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Payment</label>
                            <select name="payment_status" class="w-full px-3 py-2 text-sm text-gray-900"
                                    style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px;">
                                @foreach(['unpaid','partial','paid'] as $ps)
                                <option value="{{ $ps }}" {{ $order->payment_status === $ps ? 'selected' : '' }}>
                                    {{ ucfirst($ps) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full py-2 text-sm font-medium"
                                style="background:rgba(14,165,233,0.15); border:1px solid rgba(14,165,233,0.4); color:#0ea5e9; border-radius:4px;">
                            Save Status
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- Delete --}}
            @if(in_array($order->fulfillment_status, ['pending', 'cancelled']))
            <form method="POST" action="{{ route('orders.destroy', $order) }}"
                  onsubmit="return confirm('Delete {{ $order->order_number }}? Stock will be restored for pending orders.')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full py-2 text-sm transition"
                        style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#ef4444; border-radius:4px;">
                    🗑️ Delete Order
                </button>
            </form>
            @endif
            @endif

        </div>
    </div>

    {{-- Order Line Items --}}
    <div class="grid-panel overflow-hidden" style="border-radius:6px;">
        <div class="px-5 py-3 flex items-center justify-between" style="border-bottom:1px solid #e5e7eb;">
            <p class="text-sm font-medium text-gray-700">Line Items ({{ $order->items->count() }})</p>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                    <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">Product</th>
                    <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">Category</th>
                    <th class="text-center px-4 py-2 text-xs text-gray-500 uppercase">Qty</th>
                    <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Unit Price</th>
                    <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr style="border-bottom:1px solid #e5e7eb;" class="hover:bg-[#f9fafb] transition">
                    <td class="px-4 py-3">
                        <a href="{{ route('products.show', $item->product) }}" class="font-medium text-gray-900 hover:text-cyan-400 transition">
                            {{ $item->product->name ?? '[deleted]' }}
                        </a>
                        <p class="text-xs text-gray-500 metrics-display">{{ $item->product->sku ?? '' }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $item->product->category->icon ?? '' }} {{ $item->product->category->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-center metrics-display text-gray-700">{{ $item->quantity }}</td>
                    <td class="px-4 py-3 text-right metrics-display text-gray-700">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-4 py-3 text-right metrics-display text-gray-900 font-medium">₱{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb; border-top:1px solid #e5e7eb;">
                    <td colspan="4" class="px-4 py-3 text-sm text-right text-gray-600">Order Total</td>
                    <td class="px-4 py-3 text-right metrics-display text-lg font-bold accent-cyan">
                        ₱{{ number_format($order->total_amount, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
@endsection

