<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * DJLN Marketing — CategoryController
 *
 * Manages inventory categories (Candies, Balloons, Party Needs, etc.)
 * Replaces the old ProjectController logic.
 *
 * Routes (all inside auth + checkRole:admin,project_manager):
 *   GET    /categories              → index
 *   GET    /categories/create       → create
 *   POST   /categories              → store
 *   GET    /categories/{category}   → show
 *   GET    /categories/{category}/edit → edit
 *   PUT    /categories/{category}   → update
 *   DELETE /categories/{category}   → destroy
 */
class CategoryController extends Controller
{
    // ──────────────────────────────────────────────
    // INDEX — List all categories
    // ──────────────────────────────────────────────

    public function index(): View
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->paginate(15);

        return view('categories.index', compact('categories'));
    }

    // ──────────────────────────────────────────────
    // CREATE — Show form for new category
    // ──────────────────────────────────────────────

    public function create(): View
    {
        return view('categories.create');
    }

    // ──────────────────────────────────────────────
    // STORE — Persist new category
    // ──────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'is_active'   => ['boolean'],
        ]);

        $validated['slug']       = Str::slug($validated['name']);
        $validated['created_by'] = auth()->id();
        $validated['is_active']  = $request->boolean('is_active', true);

        // Ensure slug uniqueness
        $baseSlug = $validated['slug'];
        $count    = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $count++;
        }

        $category = Category::create($validated);

        return redirect()
            ->route('categories.show', $category)
            ->with('success', "Category \"{$category->name}\" created successfully.");
    }

    // ──────────────────────────────────────────────
    // SHOW — View a single category + its products
    // ──────────────────────────────────────────────

    public function show(Category $category): View
    {
        $category->loadCount('products');

        $products = $category->products()
            ->orderBy('name')
            ->paginate(20);

        return view('categories.show', compact('category', 'products'));
    }

    // ──────────────────────────────────────────────
    // EDIT — Show edit form
    // ──────────────────────────────────────────────

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    // ──────────────────────────────────────────────
    // UPDATE — Persist edits
    // ──────────────────────────────────────────────

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:categories,name,' . $category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'is_active'   => ['boolean'],
        ]);

        // Re-generate slug only if name changed
        if ($category->name !== $validated['name']) {
            $baseSlug        = Str::slug($validated['name']);
            $validated['slug'] = $baseSlug;
            $count = 1;
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
                $validated['slug'] = $baseSlug . '-' . $count++;
            }
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()
            ->route('categories.show', $category)
            ->with('success', "Category \"{$category->name}\" updated successfully.");
    }

    // ──────────────────────────────────────────────
    // DESTROY — Delete category (only if no products)
    // ──────────────────────────────────────────────

    public function destroy(Category $category): RedirectResponse
    {
        // Safety check — prevent deleting a category that still has products
        if ($category->products()->exists()) {
            return back()->with(
                'error',
                "Cannot delete \"{$category->name}\" — it still has " .
                $category->products()->count() . " product(s). " .
                "Move or delete the products first."
            );
        }

        $name = $category->name;
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', "Category \"{$name}\" deleted.");
    }
}
