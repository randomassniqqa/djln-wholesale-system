@extends('layouts.app')

@section('title', 'Orders — DJLN Marketing')
@section('page-title', '📋 Order Management')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Header Bar --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div style="display:flex;gap:12px;align-items:center;">
            <p style="font-size:13px;color:var(--muted);">{{ $orders->total() }} {{ Str::plural('order', $orders->total()) }} total</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            {{-- Only clients see the shop link; admins/staff see the fulfillment hint --}}
            @can('manage-orders')
                <span style="font-size:11px;color:var(--muted);padding:6px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:6px;">
                    📦 Fulfillment &amp; payment management view
                </span>
            @endcan
            @cannot('manage-orders')
                <a href="{{ route('customer.catalog') }}" class="btn btn-primary">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Shop Now
                </a>
            @endcannot
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('orders.index') }}" class="card" style="padding:16px;display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order # or Customer..." class="form-input">
        </div>
        <div style="min-width:140px;">
            <label class="form-label">Type</label>
            <select name="type" class="form-input">
                <option value="">All Types</option>
                <option value="wholesale" {{ request('type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                <option value="retail" {{ request('type') == 'retail' ? 'selected' : '' }}>Retail</option>
            </select>
        </div>
        <div style="min-width:140px;">
            <label class="form-label">Fulfillment</label>
            <select name="fulfillment" class="form-input">
                <option value="">All Statuses</option>
                <option value="pending"    {{ request('fulfillment') == 'pending'    ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ request('fulfillment') == 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="shipped"    {{ request('fulfillment') == 'shipped'    ? 'selected' : '' }}>Shipped</option>
                <option value="delivered"  {{ request('fulfillment') == 'delivered'  ? 'selected' : '' }}>Delivered</option>
                <option value="cancelled"  {{ request('fulfillment') == 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('orders.index') }}" class="btn btn-ghost">Clear</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="card" style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th style="text-align:right;">Items</th>
                    <th style="text-align:right;">Total Amount</th>
                    <th style="text-align:center;">Payment</th>
                    <th style="text-align:center;">Fulfillment</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                @php
                    $fc = match($order->fulfillment_status) {
                        'pending'    => 'badge-amber',
                        'processing' => 'badge-blue',
                        'shipped'    => 'badge-purple',
                        'delivered'  => 'badge-green',
                        'cancelled'  => 'badge-red',
                        default      => 'badge-muted',
                    };
                    $pc = match($order->payment_status) {
                        'paid'    => 'badge-green',
                        'unpaid'  => 'badge-red',
                        'partial' => 'badge-amber',
                        default   => 'badge-muted',
                    };
                    $tc = $order->order_type === 'wholesale' ? 'badge-purple' : 'badge-cyan';
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="mono" style="font-size:13px;font-weight:600;color:var(--cyan);text-decoration:none;">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td style="font-size:12px;color:var(--text2);">
                        {{ optional($order->ordered_at)->format('M d, Y') ?? '—' }}<br>
                        <span style="font-size:10px;color:var(--muted);">{{ optional($order->ordered_at)->format('h:i A') ?? '' }}</span>
                    </td>
                    <td style="font-size:13px;font-weight:500;color:var(--text);">
                        {{ $order->customer->name ?? 'Guest / Walk-in' }}
                    </td>
                    <td>
                        <span class="badge {{ $tc }}">{{ ucfirst($order->order_type) }}</span>
                    </td>
                    <td class="mono" style="text-align:right;font-size:12px;color:var(--muted);">
                        {{ $order->items_count ?? 0 }} items
                    </td>
                    <td class="mono" style="text-align:right;font-size:14px;font-weight:700;color:var(--text);">
                        ₱{{ number_format($order->total_amount, 2) }}
                    </td>
                    <td style="text-align:center;">
                        <span class="badge {{ $pc }}">{{ ucfirst($order->payment_status) }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge {{ $fc }}">{{ ucfirst($order->fulfillment_status) }}</span>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                            <a href="{{ route('orders.show', $order) }}" class="btn btn-ghost" style="padding:4px 8px;font-size:11px;">View</a>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:var(--muted);">
                        <p style="font-size:32px;">📋</p>
                        <p style="font-size:13px;margin-top:8px;">No orders match your filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div style="margin-top:10px;">
        {{ $orders->links() }}
    </div>
    @endif

</div>
@endsection

