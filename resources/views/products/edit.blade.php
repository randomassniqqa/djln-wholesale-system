@extends('layouts.app')
@section('title', 'Edit — ' . $product->name)
@section('page-title', '✏️ Edit: ' . $product->name)

@section('content')
<div style="max-width:760px;" class="space-y-5">

    <nav style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;">
        <a href="{{ route('products.index') }}" style="color:var(--cyan);text-decoration:none;">Products</a>
        <span>/</span>
        <a href="{{ route('products.show', $product) }}" style="color:var(--cyan);text-decoration:none;">{{ $product->sku }}</a>
        <span>/</span><span>Edit</span>
    </nav>

    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- ── SECTION 1: Basic Info ── --}}
        <div class="card" style="padding:24px;margin-bottom:16px;">
            <p class="section-label">📦 Basic Information</p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

                {{-- Name --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">Product Name <span style="color:var(--red);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="form-input @error('name') border-red @enderror">
                    @error('name')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- SKU --}}
                <div>
                    <label class="form-label">SKU <span style="color:var(--red);">*</span></label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required
                           class="form-input mono @error('sku') border-red @enderror">
                    @error('sku')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Category --}}
                <div>
                    <label class="form-label">Category <span style="color:var(--red);">*</span></label>
                    <select name="category_id" required class="form-input @error('category_id') border-red @enderror">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->icon }} {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Brand Name --}}
                <div>
                    <label class="form-label">Brand / Manufacturer</label>
                    <input type="text" name="brand_name"
                           value="{{ old('brand_name', $product->brand_name) }}"
                           class="form-input" placeholder="e.g. Haribo, Rebisco, Local">
                </div>

                {{-- Description --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3"
                              class="form-input" style="resize:vertical;">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── SECTION 2: Pricing ── --}}
        <div class="card" style="padding:24px;margin-bottom:16px;">
            <p class="section-label">💰 Pricing</p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label class="form-label">Wholesale Price (₱) <span style="color:var(--red);">*</span></label>
                    <input type="number" name="wholesale_price" step="0.01" min="0" required id="wp"
                           value="{{ old('wholesale_price', $product->wholesale_price) }}"
                           class="form-input mono @error('wholesale_price') border-red @enderror"
                           oninput="updateMargin()">
                    @error('wholesale_price')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Retail Price (₱) <span style="color:var(--red);">*</span></label>
                    <input type="number" name="retail_price" step="0.01" min="0" required id="rp"
                           value="{{ old('retail_price', $product->retail_price) }}"
                           class="form-input mono @error('retail_price') border-red @enderror"
                           oninput="updateMargin()">
                    @error('retail_price')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Live margin --}}
                <div style="grid-column:1/-1;">
                    <div id="margin-display" style="padding:10px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;font-size:12px;color:var(--text2);">
                        @php
                            $wp = (float) $product->wholesale_price;
                            $rp = (float) $product->retail_price;
                            $profit = $rp - $wp;
                            $pct = $wp > 0 ? round(($profit / $wp) * 100, 1) : 0;
                        @endphp
                        📊 Margin: <strong id="margin-pct" style="color:{{ $profit >= 0 ? 'var(--green)' : 'var(--red)' }};">{{ $pct }}%</strong>
                        &nbsp;·&nbsp; Profit per unit: <strong id="margin-abs" class="mono" style="color:{{ $profit >= 0 ? 'var(--green)' : 'var(--red)' }};">₱{{ number_format($profit, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SECTION 3: Wholesale Rules ── --}}
        <div class="card" style="padding:24px;margin-bottom:16px;">
            <p class="section-label">🏭 Wholesale Rules</p>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                {{-- Unit type --}}
                <div>
                    <label class="form-label">Unit Type <span style="color:var(--red);">*</span></label>
                    <select name="unit" required class="form-input">
                        @foreach(['piece' => 'Per Piece', 'kg' => 'Per kg', 'box' => 'Per Box', 'pack' => 'Per Pack', 'set' => 'Per Set', 'bag' => 'Per Bag'] as $val => $label)
                            <option value="{{ $val }}" {{ old('unit', $product->unit) === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- MOQ --}}
                <div>
                    <label class="form-label">Min. Order Qty (MOQ) <span style="color:var(--red);">*</span></label>
                    <input type="number" name="moq" min="1" required
                           value="{{ old('moq', $product->moq ?? 1) }}"
                           class="form-input mono">
                    <p style="font-size:10px;color:var(--muted);margin-top:4px;">Wholesale minimum per order</p>
                </div>

                {{-- Weight / Volume --}}
                <div>
                    <label class="form-label">Weight / Volume</label>
                    <input type="text" name="weight_volume"
                           value="{{ old('weight_volume', $product->weight_volume) }}"
                           class="form-input" placeholder="e.g. 5 kg, 200 g, 1 L">
                    <p style="font-size:10px;color:var(--muted);margin-top:4px;">For shipping estimation</p>
                </div>
            </div>
        </div>

        {{-- ── SECTION 4: Inventory ── --}}
        <div class="card" style="padding:24px;margin-bottom:16px;">
            <p class="section-label">📊 Inventory</p>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div>
                    <label class="form-label">Stock Qty <span style="color:var(--red);">*</span></label>
                    <input type="number" name="stock_qty" min="0" required
                           value="{{ old('stock_qty', $product->stock_qty) }}"
                           class="form-input mono">
                    <p style="font-size:10px;color:var(--muted);margin-top:4px;">Use Restock for arrivals</p>
                </div>
                <div>
                    <label class="form-label">Reorder Level <span style="color:var(--red);">*</span></label>
                    <input type="number" name="reorder_level" min="0" required
                           value="{{ old('reorder_level', $product->reorder_level) }}"
                           class="form-input mono">
                    <p style="font-size:10px;color:var(--muted);margin-top:4px;">Alert threshold</p>
                </div>
                <div>
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date"
                           value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}"
                           class="form-input">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">Image Upload (Leave empty to keep current)</label>
                    <input type="file" name="image" accept="image/*" class="form-input">
                    @if($product->image_url)
                        <p style="font-size:11px;color:var(--cyan);margin-top:6px;">Current: <a href="{{ Storage::url($product->image_url) }}" target="_blank">View Image</a></p>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:10px;padding-top:10px;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--cyan);">
                    <label for="is_active" class="form-label" style="margin:0;cursor:pointer;">
                        Active (visible in catalog)
                    </label>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div style="display:flex;align-items:center;gap:10px;">
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            <a href="{{ route('products.show', $product) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function updateMargin() {
    const wp     = parseFloat(document.getElementById('wp').value) || 0;
    const rp     = parseFloat(document.getElementById('rp').value) || 0;
    const profit = rp - wp;
    const pct    = wp > 0 ? ((profit / wp) * 100).toFixed(1) : 0;
    const color  = profit >= 0 ? 'var(--green)' : 'var(--red)';
    document.getElementById('margin-pct').textContent = pct + '%';
    document.getElementById('margin-pct').style.color = color;
    document.getElementById('margin-abs').textContent = '₱' + profit.toFixed(2);
    document.getElementById('margin-abs').style.color = color;
}
</script>
@endpush
@endsection
