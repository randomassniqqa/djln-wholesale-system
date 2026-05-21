@extends('layouts.shop')
@section('title', 'Order Confirmed — {{ $order->order_number }}')

@section('content')

{{-- ══════ CONFIRMATION HERO ══════ --}}
<div style="text-align:center;padding:48px 20px 32px;position:relative;">
    {{-- Animated check icon --}}
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(16,185,129,0.35);animation:pop 0.4s cubic-bezier(0.175,0.885,0.32,1.275);">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
    </div>

    <h1 style="font-size:30px;font-weight:800;color:#0f172a;letter-spacing:-0.02em;margin-bottom:8px;">
        Order Confirmed! 🎉
    </h1>
    <p style="font-size:16px;color:#64748b;margin-bottom:20px;">
        Your order has been placed and is being processed.
    </p>

    {{-- Order ID pill --}}
    <div style="display:inline-flex;align-items:center;gap:12px;background:white;border:2px solid #0ea5e9;border-radius:16px;padding:12px 24px;box-shadow:0 4px 16px rgba(14,165,233,0.15);">
        <span style="font-size:13px;color:#64748b;font-weight:600;">Order ID</span>
        <span style="font-size:20px;font-weight:800;color:#0ea5e9;font-family:monospace;letter-spacing:0.02em;">
            {{ $order->order_number }}
        </span>
    </div>
</div>

<div style="max-width:720px;margin:0 auto;">

    {{-- ══════ STATUS TRACKER ══════ --}}
    <div class="shop-panel" style="margin-bottom:20px;">
        <div style="font-weight:700;font-size:15px;color:#0f172a;margin-bottom:16px;">📍 Order Status</div>
        @php
            $steps = ['pending','processing','shipped','delivered'];
            $stepLabels = ['Order Placed','Processing','Shipped','Delivered'];
            $stepIcons  = ['📋','🔄','🚚','✅'];
            $current = array_search($order->fulfillment_status, $steps);
            if($current === false) $current = 0;
        @endphp
        <div class="status-track">
            @foreach($steps as $i => $step)
                <div class="status-step">
                    <div class="status-dot {{ $i < $current ? 'done' : ($i === $current ? 'active' : '') }}"
                         style="{{ $i <= $current ? 'font-size:16px;' : '' }}">
                        @if($i < $current) ✓
                        @else {{ $stepIcons[$i] }}
                        @endif
                    </div>
                    <div class="status-label {{ $i < $current ? 'done' : ($i === $current ? 'active' : '') }}">
                        {{ $stepLabels[$i] }}
                    </div>
                </div>
                @if(!$loop->last)
                    <div class="status-line {{ $i < $current ? 'done' : '' }}"></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ══════ ORDER DETAILS ══════ --}}
    <div class="shop-panel" style="margin-bottom:20px;">
        <div style="font-weight:700;font-size:15px;color:#0f172a;margin-bottom:16px;">🧾 Order Details</div>

        {{-- Meta row --}}
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:4px;">Date</div>
                <div style="font-weight:600;font-size:14px;color:#0f172a;">
                    {{ $order->ordered_at?->format('M d, Y g:ia') ?? $order->created_at->format('M d, Y g:ia') }}
                </div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:4px;">Type</div>
                <div style="font-weight:600;font-size:14px;color:#0f172a;">
                    {{ $order->order_type === 'wholesale' ? '🏭 Wholesale' : '🛍️ Retail' }}
                </div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:4px;">Payment</div>
                <div>
                    <span class="order-badge {{ $order->payment_status === 'paid' ? 'badge-delivered' : 'badge-pending' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Items --}}
        @foreach($order->items as $item)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f8fafc;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        {{ $item->product->category->icon ?? '📦' }}
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:14px;color:#0f172a;">{{ $item->product->name }}</div>
                        <div style="font-size:11px;color:#94a3b8;">
                            {{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}
                        </div>
                    </div>
                </div>
                <span style="font-weight:700;font-size:15px;color:#0f172a;">
                    ₱{{ number_format($item->line_total, 2) }}
                </span>
            </div>
        @endforeach

        {{-- Total --}}
        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px;margin-top:4px;">
            <span style="font-size:15px;font-weight:600;color:#64748b;">Order Total</span>
            <span style="font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-0.02em;">
                ₱{{ number_format($order->total_amount, 2) }}
            </span>
        </div>
    </div>

    {{-- ══════ NOTES ══════ --}}
    @if($order->notes)
        <div class="shop-panel" style="margin-bottom:20px;border-color:#fde68a;background:#fffbeb;">
            <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:6px;">📝 Your Notes</div>
            <p style="font-size:14px;color:#78350f;">{{ $order->notes }}</p>
        </div>
    @endif

    {{-- ══════ CTA BUTTONS ══════ --}}
    <div style="display:flex;gap:12px;justify-content:center;">
        <a href="{{ route('customer.catalog') }}" class="btn-shop-primary">
            🛒 Continue Shopping
        </a>
        <a href="{{ route('customer.dashboard') }}" class="btn-shop-secondary">
            🏠 Back to Dashboard
        </a>
    </div>
</div>

@push('scripts')
<style>
    @keyframes pop {
        0%   { transform: scale(0); }
        100% { transform: scale(1); }
    }
</style>
@endpush

@endsection
