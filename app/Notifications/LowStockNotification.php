<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Notification;

/**
 * DJLN Marketing — LowStockNotification
 *
 * Fired when a product's stock_qty drops to or below its reorder_level.
 * Delivered to all ADMIN and PROJECT_MANAGER users.
 */
class LowStockNotification extends Notification
{
    public function __construct(public readonly Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isOut = $this->product->stock_qty === 0;

        return [
            'type'         => $isOut ? 'stock_out' : 'stock_low',
            'icon'         => $isOut ? '🚨' : '⚠️',
            'color'        => $isOut ? 'red' : 'amber',
            'title'        => $isOut
                ? "Out of Stock: {$this->product->name}"
                : "Low Stock Alert: {$this->product->name}",
            'message'      => $isOut
                ? "[{$this->product->sku}] is completely out of stock. Restock immediately."
                : "[{$this->product->sku}] has only {$this->product->stock_qty} {$this->product->unit}(s) left (reorder level: {$this->product->reorder_level}).",
            'action_url'   => route('products.show', $this->product),
            'action_label' => 'View Product',
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'stock_qty'    => $this->product->stock_qty,
        ];
    }
}
