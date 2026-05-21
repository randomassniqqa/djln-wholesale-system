@extends('layouts.app')

@section('title', 'New Order — DJLN Marketing')
@section('page-title', '📋 Create New Order')

@section('content')
<div class="max-w-4xl space-y-5"
     x-data="orderForm()"
     x-init="init()">

    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="{{ route('orders.index') }}" class="hover:text-cyan-400 transition">Orders</a>
        <span>/</span><span class="text-gray-700">New</span>
    </nav>

    <form method="POST" action="{{ route('orders.store') }}" class="space-y-4">
        @csrf

        {{-- Order Header --}}
        <div class="grid-panel p-6 space-y-4" style="border-radius:6px;">
            <p class="text-xs text-gray-500 uppercase tracking-wider pb-2" style="border-bottom:1px solid #e5e7eb;">Order Details</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Order Type --}}
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Order Type <span class="text-red-400">*</span></label>
                    <select name="order_type" x-model="orderType" required
                            class="w-full px-3 py-2 text-sm text-gray-900"
                            style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px;">
                        <option value="retail">Retail (Walk-in / Single Unit)</option>
                        <option value="wholesale">Wholesale (Bulk / Dealer)</option>
                    </select>
                    @error('order_type')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Customer Name --}}
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Customer Name <span class="text-red-400">*</span></label>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                           class="w-full px-3 py-2 text-sm text-gray-900 @error('customer_name') border-red-500 @enderror"
                           style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                           placeholder="Customer or business name">
                    @error('customer_name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Discount --}}
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Discount Amount (₱)</label>
                    <input type="number" name="discount_amount" x-model.number="discount"
                           value="{{ old('discount_amount', 0) }}" step="0.01" min="0"
                           class="w-full px-3 py-2 text-sm text-gray-900 metrics-display"
                           style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                           placeholder="0.00">
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           class="w-full px-3 py-2 text-sm text-gray-900"
                           style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                           placeholder="e.g. Bulk order — Party Events Supplier">
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="grid-panel overflow-hidden" style="border-radius:6px;">
            <div class="px-5 py-3 flex items-center justify-between" style="border-bottom:1px solid #e5e7eb;">
                <p class="text-sm font-medium text-gray-700">Order Items</p>
                <button type="button" @click="addLine()"
                        class="flex items-center gap-1 text-xs px-3 py-1"
                        style="background:rgba(14,165,233,0.1); color:#0ea5e9; border:1px solid rgba(14,165,233,0.2); border-radius:4px;">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Line
                </button>
            </div>

            {{-- Validation error for items --}}
            @error('items')<p class="text-xs text-red-400 px-5 py-2">{{ $message }}</p>@enderror

            {{-- Column Headers --}}
            <div class="grid gap-3 px-5 py-2 text-xs text-gray-500 uppercase" style="grid-template-columns: 1fr 100px 120px 120px 32px; border-bottom:1px solid #e5e7eb; background:#f9fafb;">
                <span>Product</span>
                <span class="text-center">Qty</span>
                <span class="text-right">Unit Price</span>
                <span class="text-right">Line Total</span>
                <span></span>
            </div>

            {{-- Dynamic Line Items --}}
            <template x-for="(line, index) in lines" :key="index">
                <div class="grid gap-3 px-5 py-3 items-center"
                     style="grid-template-columns: 1fr 100px 120px 120px 32px; border-bottom:1px solid #e5e7eb;">

                    {{-- Product Select --}}
                    <div>
                        <select :name="'items[' + index + '][product_id]'"
                                x-model="line.product_id"
                                @change="onProductChange(index)"
                                required
                                class="w-full px-2 py-1.5 text-sm text-gray-900"
                                style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px;">
                            <option value="">— Select Product —</option>
                            @foreach($productsByCategory as $catName => $products)
                            <optgroup label="{{ $catName }}">
                                @foreach($products as $p)
                                <option value="{{ $p->id }}"
                                        data-wholesale="{{ $p->wholesale_price }}"
                                        data-retail="{{ $p->retail_price }}"
                                        data-stock="{{ $p->stock_qty }}"
                                        data-unit="{{ $p->unit }}">
                                    {{ $p->name }} ({{ $p->sku }}) — {{ $p->stock_qty }} in stock
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-600 mt-0.5" x-show="line.stock > 0">
                            Available: <span x-text="line.stock" class="metrics-display"></span>
                            <span x-text="line.unit"></span>
                        </p>
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <input type="number" :name="'items[' + index + '][quantity]'"
                               x-model.number="line.qty"
                               @input="calcLine(index)"
                               :max="line.stock"
                               min="1" required
                               class="w-full px-2 py-1.5 text-sm text-gray-900 text-center metrics-display"
                               style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                               placeholder="0">
                    </div>

                    {{-- Unit Price (auto-filled, read-only display) --}}
                    <div class="text-right">
                        <p class="metrics-display text-sm text-gray-700 py-1.5 pr-1"
                           x-text="'₱' + line.price.toFixed(2)"></p>
                        <p class="text-xs text-gray-600" x-show="orderType === 'wholesale'">Wholesale rate</p>
                        <p class="text-xs text-gray-600" x-show="orderType === 'retail'">Retail rate</p>
                    </div>

                    {{-- Line Total --}}
                    <div class="text-right">
                        <p class="metrics-display text-sm accent-cyan py-1.5"
                           x-text="'₱' + line.total.toFixed(2)"></p>
                    </div>

                    {{-- Remove --}}
                    <div class="text-center">
                        <button type="button" @click="removeLine(index)"
                                x-show="lines.length > 1"
                                class="text-red-500 hover:text-red-400 transition text-lg leading-none">×</button>
                    </div>
                </div>
            </template>

            {{-- Order Summary Footer --}}
            <div class="px-5 py-4 space-y-2" style="background:#f9fafb; border-top:1px solid #e5e7eb;">
                <div class="flex justify-end items-center gap-8 text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="metrics-display text-gray-700 min-w-[100px] text-right" x-text="'₱' + subtotal.toFixed(2)"></span>
                </div>
                <div class="flex justify-end items-center gap-8 text-sm" x-show="discount > 0">
                    <span class="text-gray-600">Discount</span>
                    <span class="metrics-display text-red-400 min-w-[100px] text-right" x-text="'− ₱' + discount.toFixed(2)"></span>
                </div>
                <div class="flex justify-end items-center gap-8 pt-2 font-semibold" style="border-top:1px solid #e5e7eb;">
                    <span class="text-gray-900">Total</span>
                    <span class="metrics-display text-xl accent-cyan min-w-[100px] text-right" x-text="'₱' + total.toFixed(2)"></span>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-2 text-sm font-medium"
                    style="background:rgba(14,165,233,0.2); border:1px solid rgba(14,165,233,0.5); color:#0ea5e9; border-radius:4px;">
                📋 Place Order
            </button>
            <a href="{{ route('orders.index') }}" class="px-6 py-2 text-sm"
               style="background:rgba(107,114,128,0.1); border:1px solid #e5e7eb; color:#6b7280; border-radius:4px;">
                Cancel
            </a>
            <p class="text-xs text-gray-600 ml-2">Stock will be deducted immediately upon placing the order.</p>
        </div>
    </form>
</div>

{{-- Alpine.js Order Form Logic --}}
<script>
function orderForm() {
    return {
        orderType: 'retail',
        discount:  0,
        lines: [],

        get subtotal() {
            return this.lines.reduce((sum, l) => sum + l.total, 0);
        },
        get total() {
            return Math.max(0, this.subtotal - this.discount);
        },

        init() {
            this.addLine();
        },

        addLine() {
            this.lines.push({ product_id: '', qty: 1, price: 0, total: 0, stock: 0, unit: '' });
        },

        removeLine(i) {
            if (this.lines.length > 1) this.lines.splice(i, 1);
        },

        onProductChange(i) {
            const line = this.lines[i];
            const select = document.querySelectorAll('select[name^="items[' + i + ']"]')[0];
            if (!select) return;
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) {
                line.price = 0; line.total = 0; line.stock = 0; line.unit = '';
                return;
            }
            const wholesale = parseFloat(opt.dataset.wholesale) || 0;
            const retail    = parseFloat(opt.dataset.retail)    || 0;
            line.stock = parseInt(opt.dataset.stock) || 0;
            line.unit  = opt.dataset.unit || '';
            line.price = this.orderType === 'wholesale' ? wholesale : retail;
            this.calcLine(i);
        },

        calcLine(i) {
            const line  = this.lines[i];
            line.total  = Math.round(line.price * (line.qty || 0) * 100) / 100;
        },
    };
}
</script>
@endsection

