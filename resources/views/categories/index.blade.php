@extends('layouts.app')

@section('title', 'Categories — DJLN Marketing')
@section('page-title', '🏷️ Product Categories')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Header Bar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <p style="font-size:13px;color:var(--muted);">{{ $categories->total() }} {{ Str::plural('category', $categories->total()) }} total</p>
        @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Category
        </a>
        @endif
    </div>

    {{-- Category Grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
        @forelse($categories as $category)
        @php
            $slug = strtolower(str_replace([' ','&','/'],'-', $category->name));
            $cc = match(true) {
                str_contains($slug,'cand')||str_contains($slug,'gumm') => ['class'=>'cat-candy',  'bar'=>'#b45309'],
                str_contains($slug,'loll') => ['class'=>'cat-lolly',  'bar'=>'#6d28d9'],
                str_contains($slug,'choc') => ['class'=>'cat-choco',  'bar'=>'#92400e'],
                str_contains($slug,'ball') => ['class'=>'cat-balloon','bar'=>'#0369a1'],
                str_contains($slug,'part') => ['class'=>'cat-party',  'bar'=>'#be185d'],
                default                    => ['class'=>'cat-default','bar'=>'#64748b'],
            };
        @endphp
        <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;background:var(--surface2);border:1px solid var(--border);">
                        {{ $category->icon ?? '📦' }}
                    </div>
                    <div>
                        <h3 style="font-size:14px;font-weight:600;color:var(--text);">{{ $category->name }}</h3>
                        <p class="mono" style="font-size:11px;color:var(--muted);">{{ $category->slug }}</p>
                    </div>
                </div>
                @if($category->is_active)
                    <span class="badge badge-green">Active</span>
                @else
                    <span class="badge badge-muted">Inactive</span>
                @endif
            </div>

            @if($category->description)
            <p style="font-size:12px;color:var(--text2);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;">
                {{ $category->description }}
            </p>
            @endif

            <div style="margin-top:auto;padding-top:12px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:baseline;gap:4px;">
                    <span class="mono" style="font-size:14px;font-weight:700;color:var(--cyan);">{{ $category->products_count }}</span>
                    <span style="font-size:11px;color:var(--muted);">{{ Str::plural('product', $category->products_count) }}</span>
                </div>
                <div style="display:flex;gap:6px;">
                    <a href="{{ route('categories.show', $category) }}" class="btn btn-ghost" style="padding:4px 10px;font-size:11px;">View</a>
                    @if(auth()->user()->isAdmin() || auth()->user()->isProjectManager())
                    <a href="{{ route('categories.edit', $category) }}" class="btn" style="padding:4px 10px;font-size:11px;background:#fffbeb;color:#d97706;border-color:#fde68a;">Edit</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="card" style="grid-column:1/-1;padding:40px;text-align:center;">
            <p style="font-size:32px;">📦</p>
            <p style="font-size:13px;color:var(--muted);margin:8px 0;">No categories found.</p>
            <a href="{{ route('categories.create') }}" class="btn btn-primary" style="margin-top:12px;">Create your first category</a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($categories->hasPages())
    <div style="margin-top:10px;">
        {{ $categories->links() }}
    </div>
    @endif

</div>
@endsection

