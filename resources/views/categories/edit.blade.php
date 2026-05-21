@extends('layouts.app')

@section('title', 'Edit — ' . $category->name)
@section('page-title', '✏️ Edit: ' . $category->name)

@section('content')
<div class="max-w-xl space-y-5">

    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="{{ route('categories.index') }}" class="hover:text-cyan-400 transition">Categories</a>
        <span>/</span>
        <a href="{{ route('categories.show', $category) }}" class="hover:text-cyan-400 transition">{{ $category->name }}</a>
        <span>/</span><span class="text-gray-700">Edit</span>
    </nav>

    <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-4">
        @csrf @method('PUT')

        <div class="grid-panel p-6 space-y-4" style="border-radius:6px;">
            <p class="text-xs text-gray-500 uppercase tracking-wider pb-2" style="border-bottom:1px solid #e5e7eb;">Category Details</p>

            <div>
                <label class="block text-xs text-gray-600 mb-1">Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                       class="w-full px-3 py-2 text-sm text-gray-900 @error('name') border-red-500 @enderror"
                       style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;">
                @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs text-gray-600 mb-1">Icon (emoji)</label>
                <input type="text" name="icon" value="{{ old('icon', $category->icon) }}" maxlength="10"
                       class="w-full px-3 py-2 text-sm text-gray-900"
                       style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;">
            </div>

            <div>
                <label class="block text-xs text-gray-600 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 text-sm text-gray-900 resize-none"
                          style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px; outline:none;">{{ old('description', $category->description) }}</textarea>
            </div>

            {{-- Slug preview (read-only) --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Current Slug (auto-updates if name changes)</label>
                <p class="metrics-display text-xs text-gray-600 px-3 py-2"
                   style="background:#ffffff; border:1px solid #e5e7eb; border-radius:4px;">{{ $category->slug }}</p>
            </div>

            <div class="flex items-center gap-3 pt-2" style="border-top:1px solid #e5e7eb;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="w-4 h-4">
                <label for="is_active" class="text-sm text-gray-700 cursor-pointer">Active (visible in product selectors)</label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-2 text-sm font-medium"
                    style="background:rgba(14,165,233,0.2); border:1px solid rgba(14,165,233,0.5); color:#0ea5e9; border-radius:4px;">
                💾 Save Changes
            </button>
            <a href="{{ route('categories.show', $category) }}" class="px-6 py-2 text-sm"
               style="background:rgba(107,114,128,0.1); border:1px solid #e5e7eb; color:#6b7280; border-radius:4px;">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

