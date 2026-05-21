<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * DJLN Marketing — Product Model
 *
 * Replaces the old Task model.
 * Represents a single SKU sold wholesale or retail
 * (e.g., Gummy Bears 5kg, Latex Balloons 100ct).
 *
 * @property int    $id
 * @property int    $category_id
 * @property string $name
 * @property string $sku
 * @property string $unit
 * @property float  $wholesale_price
 * @property float  $retail_price
 * @property int    $stock_qty
 * @property int    $reorder_level
 * @property string $stock_status   in_stock|low_stock|out_of_stock|discontinued
 * @property string|null $expiry_date
 * @property bool   $is_active
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'brand_name',
        'weight_volume',
        'unit',
        'wholesale_price',
        'retail_price',
        'stock_qty',
        'reorder_level',
        'moq',
        'stock_status',
        'expiry_date',
        'image_url',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'wholesale_price' => 'decimal:2',
        'retail_price'    => 'decimal:2',
        'stock_qty'       => 'integer',
        'reorder_level'   => 'integer',
        'moq'             => 'integer',
        'is_active'       => 'boolean',
        'expiry_date'     => 'date',
    ];

    // ──────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────

    /** Product belongs to a category (e.g., "Candies & Gummies") */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Product was created/managed by a staff user */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Product appears in many order line-items */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ──────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────

    /**
     * Gross margin percentage: (retail - wholesale) / retail × 100
     */
    public function getMarginPercentAttribute(): float
    {
        if ($this->retail_price <= 0) {
            return 0;
        }
        return round(
            (($this->retail_price - $this->wholesale_price) / $this->retail_price) * 100,
            2
        );
    }

    /**
     * True when stock_qty is at or below reorder_level.
     */
    public function getNeedsReorderAttribute(): bool
    {
        return $this->stock_qty <= $this->reorder_level;
    }

    // ──────────────────────────────────────────────
    // BUSINESS LOGIC
    // ──────────────────────────────────────────────

    /**
     * Return the applicable unit price based on order type.
     *
     * @param  'wholesale'|'retail'  $type
     */
    public function getPriceFor(string $type): float
    {
        return $type === 'wholesale'
            ? (float) $this->wholesale_price
            : (float) $this->retail_price;
    }

    /**
     * Decrement stock and auto-update stock_status.
     * Returns false if insufficient stock.
     */
    public function deductStock(int $qty): bool
    {
        if ($this->stock_qty < $qty) {
            return false;
        }
        $newQty = $this->stock_qty - $qty;
        $this->stock_qty = $newQty;
        $this->stock_status = $this->resolveStockStatus($newQty);
        $this->save();
        return true;
    }

    /**
     * Increment stock after a return or restocking event.
     */
    public function restockBy(int $qty): void
    {
        $newQty = $this->stock_qty + $qty;
        $this->stock_qty = $newQty;
        $this->stock_status = $this->resolveStockStatus($newQty);
        $this->save();
    }

    /**
     * Derive the correct stock_status from a quantity.
     */
    public function resolveStockStatus(int $qty): string
    {
        if ($qty === 0) {
            return 'out_of_stock';
        }
        if ($qty <= $this->reorder_level) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    // ──────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────

    /** Only active, saleable products */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Products currently in stock */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_status', 'in_stock')
                     ->orWhere('stock_status', 'low_stock');
    }

    /** Products needing a reorder */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_qty', '<=', 'reorder_level')
                     ->where('stock_status', '!=', 'discontinued');
    }

    /** Filter by category */
    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /** Search by name or SKU */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
