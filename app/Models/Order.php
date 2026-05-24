<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * DJLN Marketing — Order Model
 *
 * Represents a wholesale or retail sale.
 * fulfillment_status replaces old task_status logic.
 */
class Order extends Model
{
    protected $table = 'wholesale_transactions';

    protected $fillable = [
        'order_number',
        'customer_id',
        'processed_by',
        'order_type',
        'fulfillment_status',
        'payment_status',
        'subtotal',
        'discount_amount',
        'total_amount',
        'notes',
        'ordered_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'ordered_at'      => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ──────────────────────────────────────────────
    // BUSINESS LOGIC
    // ──────────────────────────────────────────────

    /** Generate next sequential order number */
    public static function generateOrderNumber(): string
    {
        $year  = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('DJLN-%s-%05d', $year, $count);
    }

    /** Recalculate totals from items */
    public function recalculateTotals(): void
    {
        $this->subtotal     = round((float) $this->items()->sum('line_total'), 2);
        $this->total_amount = round(max(0, (float) $this->subtotal - (float) $this->discount_amount), 2);
        $this->save();
    }

    public function isWholesale(): bool
    {
        return $this->order_type === 'wholesale';
    }

    public function isPending(): bool
    {
        return $this->fulfillment_status === 'pending';
    }

    public function isDelivered(): bool
    {
        return $this->fulfillment_status === 'delivered';
    }

    // ──────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────

    public function scopeWholesale(Builder $query): Builder
    {
        return $query->where('order_type', 'wholesale');
    }

    public function scopeRetail(Builder $query): Builder
    {
        return $query->where('order_type', 'retail');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('fulfillment_status', $status);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }
}
