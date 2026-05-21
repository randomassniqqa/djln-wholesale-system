<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DJLN Marketing — OrderItem Model
 *
 * One line in an order: product × quantity × locked price.
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity'   => 'integer',
    ];

    // ──────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ──────────────────────────────────────────────
    // BOOT: auto-compute line_total
    // ──────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item) {
            $item->line_total = round((int) $item->quantity * (float) $item->unit_price, 2);
        });
    }
}
