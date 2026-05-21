@extends('layouts.app')

@section('title', 'New Category — DJLN Marketing')
@section('page-title', '🏷️ New Category')

@section('content')
<div class="max-w-xl space-y-5">

    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="{{ route('categories.index') }}" class="hover:text-cyan-400 transition">Categories</a>
        <span>/</span><span class="text-gray-700">New</span>
    </nav>

    <form method="POST" action="{{ route('categories.store') }}" class="space-y-4">
        @csrf

        <div class="grid-panel p-6 space-y-4" style="border-radius:6px;">
            <p class="text-xs text-gray-500 uppercase tracking-wider pb-2" style="border-bottom:1px solid #e5e7eb;">Category Details</p>

            {{-- Name --}}
            <div>
                <label class="block text-xs text-gray-600 mb-1">Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-3 py-2 text-sm text-gray-900 @error('name') border-red-500 @enderror"
                       style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                       placeholder="e.g. Candies & Gummies">
                @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Icon --}}
            <div>
                <label class="block text-xs text-gray-600 mb-1">Icon (emoji)</label>
                <input type="text" name="icon" value="{{ old('icon') }}" maxlength="10"
                       class="w-full px-3 py-2 text-sm text-gray-900"
                       style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                       placeholder="🍬">
                <p class="text-xs text-gray-600 mt-1">Paste a single emoji to represent this category.</p>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-xs text-gray-600 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 text-sm text-gray-900 resize-none"
                          style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;"
                          placeholder="Brief description of this product category...">{{ old('description') }}</textarea>
                @error('description')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Active --}}
            <div class="flex items-center gap-3 pt-2" style="border-top:1px solid #e5e7eb;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', 1) ? 'checked' : '' }} class="w-4 h-4">
                <label for="is_active" class="text-sm text-gray-700 cursor-pointer">Active (visible in product selectors)</label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-2 text-sm font-medium"
                    style="background:rgba(14,165,233,0.2); border:1px solid rgba(14,165,233,0.5); color:#0ea5e9; border-radius:4px;">
                💾 Save Category
            </button>
            <a href="{{ route('categories.index') }}" class="px-6 py-2 text-sm"
               style="background:rgba(107,114,128,0.1); border:1px solid #e5e7eb; color:#6b7280; border-radius:4px;">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

