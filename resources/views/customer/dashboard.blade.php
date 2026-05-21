@extends('layouts.shop')
@section('title', 'My Dashboard — DJLN Shop')

@section('content')

{{-- ══════ WELCOME BANNER ══════ --}}
<div class="welcome-banner">
    <div class="welcome-title">Hello, {{ auth()->user()->name }}! 👋</div>
    <div class="welcome-sub">What are we stocking up on today?</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">

    {{-- ══════ LATEST ORDER STATUS ══════ --}}
    <div class="shop-panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div class="section-title" style="margin-bottom:0;">📦 Where's My Stuff?</div>
            <a href="{{ route('customer.catalog') }}" class="btn-shop-secondary" style="font-size:12px;padding:6px 14px;">
                New Order →
            </a>
        </div>

        @if($latestOrder)
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div>
                        <div style="font-weight:700;font-size:15px;color:#0f172a;font-family:monospace;">
                            {{ $latestOrder->order_number }}
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                            {{ $latestOrder->ordered_at?->format('M d, Y') ?? $latestOrder->created_at->format('M d, Y') }}
                            · {{ $latestOrder->items->count() }} item(s)
                        </div>
                    </div>
                    <span class="order-badge badge-{{ $latestOrder->fulfillment_status }}">
                        @switch($latestOrder->fulfillment_status)
                            @case('pending')    ⏳ Pending @break
                            @case('processing') 🔄 Processing @break
                            @case('shipped')    🚚 Shipped @break
                            @case('delivered')  ✅ Delivered @break
                            @case('cancelled')  ❌ Cancelled @break
                            @default {{ ucfirst($latestOrder->fulfillment_status) }}
                        @endswitch
                    </span>
                </div>

                {{-- Status progress tracker --}}
                @php
                    $steps = ['pending','processing','shipped','delivered'];
                    $current = array_search($latestOrder->fulfillment_status, $steps);
                    if($current === false) $current = -1;
                @endphp
                <div class="status-track">
                    @foreach($steps as $i => $step)
                        <div class="status-step">
                            <div class="status-dot {{ $i < $current ? 'done' : ($i === $current ? 'active' : '') }}">
                                @if($i < $current) ✓
                                @elseif($i === $current) {{ $i + 1 }}
                                @else {{ $i + 1 }}
                                @endif
                            </div>
                            <div class="status-label {{ $i < $current ? 'done' : ($i === $current ? 'active' : '') }}">
                                {{ ucfirst($step) }}
                            </div>
                        </div>
                        @if(!$loop->last)
                            <div class="status-line {{ $i < $current ? 'done' : '' }}"></div>
                        @endif
                    @endforeach
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;">
                    <span style="font-size:12px;color:#64748b;">Total</span>
                    <span style="font-weight:800;font-size:17px;color:#0f172a;">
                        ₱{{ number_format($latestOrder->total_amount, 2) }}
                    </span>
                </div>
            </div>

            {{-- Recent orders list --}}
            @if($recentOrders->count() > 1)
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:8px;">
                    Previous Orders
                </div>
                @foreach($recentOrders->skip(1) as $order)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                        <div>
                            <div style="font-size:13px;font-weight:600;color:#0f172a;font-family:monospace;">
                                {{ $order->order_number }}
                            </div>
                            <div style="font-size:11px;color:#94a3b8;">
                                {{ $order->created_at->format('M d') }} · {{ $order->items_count }} items
                            </div>
                        </div>
                        <span class="order-badge badge-{{ $order->fulfillment_status }}" style="font-size:10px;">
                            {{ ucfirst($order->fulfillment_status) }}
                        </span>
                    </div>
                @endforeach
            @endif

        @else
            <div style="text-align:center;padding:32px 16px;color:#94a3b8;">
                <div style="font-size:40px;margin-bottom:12px;">📭</div>
                <div style="font-weight:600;margin-bottom:6px;color:#64748b;">No orders yet</div>
                <div style="font-size:12px;margin-bottom:16px;">Place your first wholesale order to get started!</div>
                <a href="{{ route('customer.catalog') }}" class="btn-shop-primary" style="font-size:13px;padding:9px 20px;">
                    Browse Products
                </a>
            </div>
        @endif
    </div>

    {{-- ══════ BUY AGAIN ══════ --}}
    <div class="shop-panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div class="section-title" style="margin-bottom:0;">🔁 Buy Again</div>
            <span style="font-size:11px;color:#94a3b8;">Your frequent items</span>
        </div>

        @if($buyAgain->isNotEmpty())
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($buyAgain as $item)
                    @php $product = $item->product; @endphp
                    <div style="display:flex;align-items:center;gap:14px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;transition:all 0.15s;"
                         onmouseover="this.style.borderColor='#bae6fd';this.style.background='#f0f9ff';"
                         onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';">

                        {{-- Product emoji icon --}}
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">
                            {{ $product->category->icon ?? '📦' }}
                        </div>

                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:14px;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $product->name }}
                            </div>
                            <div style="font-size:11px;color:#94a3b8;">
                                ₱{{ number_format($product->wholesale_price, 2) }} / {{ $product->unit }}
                                · {{ $item->total_qty }} ordered
                            </div>
                        </div>

                        <form method="POST" action="{{ route('customer.cart.add') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit"
                                class="{{ $product->stock_status === 'out_of_stock' ? 'btn-shop-secondary' : 'btn-shop-primary' }}"
                                style="font-size:12px;padding:7px 14px;border-radius:10px;"
                                {{ $product->stock_status === 'out_of_stock' ? 'disabled' : '' }}>
                                {{ $product->stock_status === 'out_of_stock' ? 'Sold Out' : '+ Add' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:16px;text-align:center;">
                <a href="{{ route('customer.catalog') }}" class="btn-shop-secondary" style="width:100%;justify-content:center;">
                    View Full Catalog →
                </a>
            </div>
        @else
            <div style="text-align:center;padding:32px 16px;color:#94a3b8;">
                <div style="font-size:40px;margin-bottom:12px;">🛍️</div>
                <div style="font-size:13px;font-weight:500;color:#64748b;margin-bottom:16px;">
                    Your frequently bought items will appear here after your first order.
                </div>
                <a href="{{ route('customer.catalog') }}" class="btn-shop-primary" style="font-size:13px;padding:9px 20px;">
                    Start Shopping
                </a>
            </div>
        @endif
    </div>
</div>

{{-- ══════ QUICK LINKS ══════ --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
    <a href="{{ route('customer.catalog') }}" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px;background:white;border:1.5px solid #e2e8f0;border-radius:16px;text-decoration:none;transition:all 0.2s;"
       onmouseover="this.style.borderColor='#0ea5e9';this.style.transform='translateY(-2px)';"
       onmouseout="this.style.borderColor='#e2e8f0';this.style.transform='translateY(0)';">
        <div style="font-size:32px;">🛒</div>
        <div style="font-weight:700;color:#0f172a;font-size:14px;">Shop Catalog</div>
        <div style="font-size:11px;color:#94a3b8;text-align:center;">Browse all products & add to cart</div>
    </a>
    <a href="{{ route('customer.cart') }}" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px;background:white;border:1.5px solid #e2e8f0;border-radius:16px;text-decoration:none;transition:all 0.2s;"
       onmouseover="this.style.borderColor='#6366f1';this.style.transform='translateY(-2px)';"
       onmouseout="this.style.borderColor='#e2e8f0';this.style.transform='translateY(0)';">
        <div style="font-size:32px;">🧾</div>
        <div style="font-weight:700;color:#0f172a;font-size:14px;">My Cart</div>
        <div style="font-size:11px;color:#94a3b8;text-align:center;">Review & place your current order</div>
    </a>
    <div style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px;background:white;border:1.5px solid #e2e8f0;border-radius:16px;">
        <div style="font-size:32px;">📞</div>
        <div style="font-weight:700;color:#0f172a;font-size:14px;">Need Help?</div>
        <div style="font-size:11px;color:#94a3b8;text-align:center;">Contact our sales team anytime</div>
    </div>
</div>

@endsection
