<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * DJLN Marketing — Category Model
 *
 * Replaces the old Project model.
 * Groups products into logical business lines
 * (Candies & Gummies, Balloons, Party Needs, etc.)
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'icon',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ──────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ──────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────

    /** Total stock value at retail price across all products in category */
    public function getRetailStockValueAttribute(): float
    {
        return (float) $this->products->sum(fn($p) => $p->retail_price * $p->stock_qty);
    }

    /** Total products in category */
    public function getProductCountAttribute(): int
    {
        return $this->products_count ?? $this->products()->count();
    }

    // ──────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
