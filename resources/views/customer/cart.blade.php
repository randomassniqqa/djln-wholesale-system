@extends('layouts.shop')
@section('title', 'My Cart — DJLN Shop')

@section('content')

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

    {{-- ══════ CART ITEMS ══════ --}}
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">
                🧾 Your Cart
            </h1>
            @if(!empty($cartItems))
                <a href="{{ route('customer.catalog') }}" class="btn-shop-secondary">
                    + Add More Items
                </a>
            @endif
        </div>

        @if(empty($cartItems))
            <div class="shop-panel" style="text-align:center;padding:60px 20px;">
                <div style="font-size:56px;margin-bottom:16px;">🛒</div>
                <div style="font-weight:700;font-size:18px;color:#0f172a;margin-bottom:8px;">Your cart is empty</div>
                <div style="color:#94a3b8;font-size:14px;margin-bottom:24px;">
                    Browse the catalog and add products to get started!
                </div>
                <a href="{{ route('customer.catalog') }}" class="btn-shop-primary">
                    Browse Products
                </a>
            </div>
        @else
            <div class="shop-panel" style="padding:0;overflow:hidden;">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cartItems as $item)
                            @php $product = $item['product']; @endphp
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div style="width:44px;height:44px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                                            {{ $product->category->icon ?? '📦' }}
                                        </div>
                                        <div>
                                            <div style="font-weight:700;color:#0f172a;font-size:14px;">
                                                {{ $product->name }}
                                            </div>
                                            <div style="font-size:11px;color:#94a3b8;">
                                                {{ $product->category->name ?? '' }} · per {{ $product->unit }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:600;color:#0f172a;">
                                        ₱{{ number_format($item['unit_price'], 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight:700;font-size:16px;color:#0f172a;">
                                        {{ $item['quantity'] }}
                                    </span>
                                    <span style="font-size:11px;color:#94a3b8;"> {{ $product->unit }}(s)</span>
                                </td>
                                <td>
                                    <span style="font-weight:800;font-size:16px;color:#0f172a;">
                                        ₱{{ number_format($item['line_total'], 2) }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('customer.cart.remove') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <button type="submit" class="btn-danger-soft">
                                            🗑 Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ══════ ORDER SUMMARY & CHECKOUT ══════ --}}
    @if(!empty($cartItems))
        <div style="position:sticky;top:90px;">
            <div class="shop-panel">
                <div style="font-size:16px;font-weight:700;color:#0f172a;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #e2e8f0;">
                    📋 Order Summary
                </div>

                {{-- Line totals --}}
                @foreach($cartItems as $item)
                    <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;">
                        <span style="color:#475569;">{{ $item['product']->name }} × {{ $item['quantity'] }}</span>
                        <span style="font-weight:600;color:#0f172a;">₱{{ number_format($item['line_total'], 2) }}</span>
                    </div>
                @endforeach

                {{-- Subtotal --}}
                <div style="border-top:1px solid #e2e8f0;margin:16px 0;padding-top:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:13px;color:#64748b;">Subtotal</span>
                        <span style="font-size:20px;font-weight:800;color:#0f172a;">₱{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <p style="font-size:11px;color:#94a3b8;margin-top:6px;">
                        VAT and discounts applied at invoice.
                    </p>
                </div>

                {{-- Place order form --}}
                <form method="POST" action="{{ route('customer.order.place') }}" id="checkout-form">
                    @csrf

                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;display:block;">
                            Order Type
                        </label>
                        <select name="order_type" required
                                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;background:white;color:#0f172a;">
                            <option value="wholesale">🏭 Wholesale</option>
                            <option value="retail">🛍️ Retail</option>
                        </select>
                    </div>

                    <div style="margin-bottom:18px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;display:block;">
                            Order Notes <span style="font-weight:400;color:#94a3b8;">(optional)</span>
                        </label>
                        <textarea name="notes" rows="2"
                                  style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;resize:none;font-family:inherit;"
                                  placeholder="Delivery instructions, special requests…">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn-shop-primary" id="place-order-btn"
                            style="width:100%;justify-content:center;font-size:15px;padding:14px;"
                            onclick="this.disabled=true;this.textContent='Placing order…';this.form.submit();">
                        ✅ Place Order
                    </button>
                </form>

                <div style="margin-top:14px;text-align:center;">
                    <a href="{{ route('customer.catalog') }}" style="font-size:12px;color:#94a3b8;text-decoration:none;">
                        ← Continue Shopping
                    </a>
                </div>


            </div>
        </div>
    @endif
</div>

@endsection
