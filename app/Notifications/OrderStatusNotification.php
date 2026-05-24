<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * DJLN Marketing — OrderStatusNotification
 *
 * Fired when an admin/PM updates an order's fulfillment_status.
 * Delivered to:
 *   - The CUSTOMER who placed the order (order status update)
 *   - All ADMIN/STAFF users (new order placed alert)
 */
class OrderStatusNotification extends Notification
{
    public function __construct(
        public readonly Order  $order,
        public readonly string $event,   // 'placed' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
        public readonly string $triggeredByName = 'System'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isClient = $notifiable->role === 'client';

        return match ($this->event) {

            'placed' => [
                'type'       => 'order_placed',
                'icon'       => '🛒',
                'color'      => 'cyan',
                'title'      => $isClient
                    ? "Order {$this->order->order_number} Confirmed"
                    : "New Order Received",
                'message'    => $isClient
                    ? "Your order for ₱" . number_format($this->order->total_amount, 2) . " has been placed and is pending processing."
                    : "{$this->order->customer->name} placed order {$this->order->order_number} — ₱" . number_format($this->order->total_amount, 2) . ".",
                'action_url' => $isClient
                    ? route('customer.order-confirmation', $this->order)
                    : route('orders.show', $this->order),
                'action_label' => 'View Order',
                'order_id'   => $this->order->id,
                'order_number' => $this->order->order_number,
            ],

            'processing' => [
                'type'       => 'order_processing',
                'icon'       => '⚙️',
                'color'      => 'blue',
                'title'      => "Order {$this->order->order_number} is Being Processed",
                'message'    => "Your order is now being prepared by our team.",
                'action_url' => route('customer.order-confirmation', $this->order),
                'action_label' => 'Track Order',
                'order_id'   => $this->order->id,
                'order_number' => $this->order->order_number,
            ],

            'shipped' => [
                'type'       => 'order_shipped',
                'icon'       => '🚚',
                'color'      => 'purple',
                'title'      => "Order {$this->order->order_number} Has Been Shipped",
                'message'    => "Your order is on its way! It will arrive soon.",
                'action_url' => route('customer.order-confirmation', $this->order),
                'action_label' => 'View Details',
                'order_id'   => $this->order->id,
                'order_number' => $this->order->order_number,
            ],

            'delivered' => [
                'type'       => 'order_delivered',
                'icon'       => '✅',
                'color'      => 'green',
                'title'      => "Order {$this->order->order_number} Delivered!",
                'message'    => "Your order has been successfully delivered. Thank you for shopping with DJLN Marketing!",
                'action_url' => route('customer.order-confirmation', $this->order),
                'action_label' => 'View Receipt',
                'order_id'   => $this->order->id,
                'order_number' => $this->order->order_number,
            ],

            'cancelled' => [
                'type'       => 'order_cancelled',
                'icon'       => '❌',
                'color'      => 'red',
                'title'      => "Order {$this->order->order_number} Cancelled",
                'message'    => $isClient
                    ? "Your order has been cancelled. Stock has been restored. Contact us for assistance."
                    : "Order {$this->order->order_number} was cancelled by {$this->triggeredByName}. Stock restored.",
                'action_url' => $isClient
                    ? route('customer.order-confirmation', $this->order)
                    : route('orders.show', $this->order),
                'action_label' => 'View Order',
                'order_id'   => $this->order->id,
                'order_number' => $this->order->order_number,
            ],

            default => [
                'type'       => 'order_update',
                'icon'       => '📋',
                'color'      => 'muted',
                'title'      => "Order {$this->order->order_number} Updated",
                'message'    => "Your order status has been updated.",
                'action_url' => route('orders.show', $this->order),
                'action_label' => 'View Order',
                'order_id'   => $this->order->id,
                'order_number' => $this->order->order_number,
            ],
        };
    }
}
