@extends('layouts.app')
@section('title', 'Add Product — DJLN Marketing')
@section('page-title', '🛒 Add New Product')

@section('content')
<div style="max-width:580px;margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb --}}
    <nav style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;margin-bottom:24px;">
        <a href="{{ route('products.index') }}" style="color:var(--cyan);text-decoration:none;font-weight:500;">Products</a>
        <span>/</span><span style="color:var(--text);">New</span>
    </nav>

    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:24px;">
        @csrf

        {{-- ── SECTION: Image Upload (Drag & Drop) ── --}}
        <div style="background:#ffffff;border:2px dashed rgba(6,182,212,0.4);border-radius:16px;padding:40px 20px;text-align:center;position:relative;transition:all 0.2s ease;" id="drop-zone" ondragover="this.style.background='rgba(6,182,212,0.05)';this.style.borderColor='var(--cyan)';" ondragleave="this.style.background='#ffffff';this.style.borderColor='rgba(6,182,212,0.4)';">
            <input type="file" name="image" id="file-input" accept="image/*" style="opacity:0;position:absolute;top:0;left:0;width:100%;height:100%;cursor:pointer;" onchange="previewImage(this)">
            
            <div id="upload-prompt">
                <div style="width:56px;height:56px;background:rgba(6,182,212,0.1);color:var(--cyan);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <svg style="width:28px;height:28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <p style="font-weight:600;color:var(--text);font-size:15px;margin-bottom:6px;">Drag & Drop Product Image</p>
                <p style="font-size:13px;color:var(--muted);">or click to browse from your device</p>
            </div>
            
            <div id="image-preview" style="display:none;">
                <img id="preview-img" src="" style="max-height:180px;margin:0 auto;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.08);">
                <p style="font-size:13px;color:var(--cyan);margin-top:16px;font-weight:500;">Click or drag a new image to replace</p>
            </div>
            
            {{-- Hidden input to bypass url validation currently in controller if no backend storage --}}
            <input type="hidden" name="image_url" value="">
        </div>

        {{-- ── SECTION: Basic Info ── --}}
        <div style="background:#ffffff;border:1px solid rgba(6,182,212,0.15);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(0,0,0,0.02);">
            <div style="display:flex;flex-direction:column;gap:20px;">
                <div>
                    <label class="form-label" style="color:var(--text);font-weight:600;margin-bottom:8px;font-size:13px;">Product Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="form-input" style="border:1px solid rgba(6,182,212,0.25);padding:14px;border-radius:10px;background:#fcfcfc;font-size:14px;"
                           placeholder="e.g. Premium Foil Balloons">
                    @error('name')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                
                <div>
                    <label class="form-label" style="color:var(--text);font-weight:600;margin-bottom:8px;font-size:13px;">Description</label>
                    <textarea name="description" rows="3"
                              class="form-input" style="border:1px solid rgba(6,182,212,0.25);padding:14px;border-radius:10px;resize:vertical;background:#fcfcfc;font-size:14px;"
                              placeholder="Brief details about the product...">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── SECTION: Categorization ── --}}
        <div style="background:#ffffff;border:1px solid rgba(6,182,212,0.15);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(0,0,0,0.02);">
            <label class="form-label" style="color:var(--text);font-weight:600;margin-bottom:8px;font-size:13px;">Category</label>
            <select name="category_id" required class="form-input" style="border:1px solid rgba(6,182,212,0.25);padding:14px;border-radius:10px;background:#fcfcfc;font-size:14px;cursor:pointer;">
                <option value="">— Select Category —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->icon }} {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
        </div>

        {{-- ── SECTION: Pricing & Stock ── --}}
        <div style="background:#ffffff;border:1px solid rgba(6,182,212,0.15);border-radius:16px;padding:28px;box-shadow:0 4px 12px rgba(0,0,0,0.02);">
            <div style="display:flex;flex-direction:column;gap:20px;">
                
                <div style="display:flex;gap:20px;">
                    <div style="flex:1;">
                        <label class="form-label" style="color:var(--text);font-weight:600;margin-bottom:8px;font-size:13px;">Wholesale Price (₱)</label>
                        <input type="number" name="wholesale_price" value="{{ old('wholesale_price') }}" step="0.01" min="0" required 
                               class="form-input" style="border:1px solid rgba(6,182,212,0.25);padding:14px;border-radius:10px;background:#fcfcfc;font-size:14px;"
                               placeholder="0.00">
                    </div>
                    
                    {{-- Required by backend but kept minimal alongside Wholesale --}}
                    <div style="flex:1;">
                        <label class="form-label" style="color:var(--text);font-weight:600;margin-bottom:8px;font-size:13px;">Retail Price (₱)</label>
                        <input type="number" name="retail_price" value="{{ old('retail_price') }}" step="0.01" min="0" required 
                               class="form-input" style="border:1px solid rgba(6,182,212,0.25);padding:14px;border-radius:10px;background:#fcfcfc;font-size:14px;"
                               placeholder="0.00">
                    </div>
                </div>

                <div style="display:flex;gap:20px;">
                    <div style="flex:1;">
                        <label class="form-label" style="color:var(--text);font-weight:600;margin-bottom:8px;font-size:13px;">Unit Type</label>
                        <select name="unit" required class="form-input" style="border:1px solid rgba(6,182,212,0.25);padding:14px;border-radius:10px;background:#fcfcfc;font-size:14px;cursor:pointer;">
                            @foreach(['pack' => 'Per Pack', 'box' => 'Per Box', 'piece' => 'Per Piece', 'kg' => 'Per kg', 'set' => 'Per Set', 'bag' => 'Per Bag'] as $val => $label)
                                <option value="{{ $val }}" {{ old('unit', 'pack') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex:1;">
                        <label class="form-label" style="color:var(--text);font-weight:600;margin-bottom:8px;font-size:13px;">Initial Stock</label>
                        <input type="number" name="stock_qty" value="{{ old('stock_qty', 0) }}" min="0" required 
                               class="form-input" style="border:1px solid rgba(6,182,212,0.25);padding:14px;border-radius:10px;background:#fcfcfc;font-size:14px;">
                    </div>
                </div>
                
                {{-- Hidden defaults required by backend schema to prevent errors --}}
                <input type="hidden" name="reorder_level" value="{{ old('reorder_level', 10) }}">
                <input type="hidden" name="moq" value="{{ old('moq', 1) }}">
                <input type="hidden" name="is_active" value="1">
            </div>
        </div>

        {{-- Submit Button --}}
        <div style="margin-top:12px;margin-bottom:40px;">
            <button type="submit" class="btn" style="width:100%;padding:18px;font-size:16px;font-weight:700;background:var(--cyan);color:white;border-radius:12px;box-shadow:0 8px 20px rgba(6,182,212,0.25);border:none;cursor:pointer;transition:all 0.2s ease;">
                + Add Product
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const prompt = document.getElementById('upload-prompt');
    const img = document.getElementById('preview-img');
    const dropZone = document.getElementById('drop-zone');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            prompt.style.display = 'none';
            preview.style.display = 'block';
            dropZone.style.border = '2px solid var(--cyan)';
            dropZone.style.background = '#fcfcfc';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection
