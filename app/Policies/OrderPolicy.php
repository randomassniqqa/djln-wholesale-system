<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * OrderPolicy — DJLN Marketing Wholesale System
 *
 * Business Rule:
 *   - CUSTOMERS (role=client) place orders exclusively via the /shop portal
 *   - ADMINS / MANAGERS / STAFF view, process, and fulfill orders
 *
 * Gate mapping:
 *   create   → client only   (shop flow; hides "Create Order" btn from staff)
 *   viewAny  → staff only    (order management index)
 *   view     → staff OR own order
 *   update   → admin / project_manager only (status changes)
 *   delete   → admin only
 */
class OrderPolicy
{
    /**
     * Only customers can "create" orders.
     * This gate controls whether the "+ Create Order" button
     * is visible in the admin UI via @can('create', Order::class).
     * Since clients are blocked from /orders by customerGuard,
     * this effectively removes the button from all admin views.
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Admins and staff can view the order management list.
     * Customers are blocked by customerGuard before reaching this.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->isProjectManager()
            || $user->isTeamMember();
    }

    /**
     * Staff can view any order detail.
     * A customer can only view their own order (for order-confirmation route).
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isClient()) {
            return $order->customer_id === $user->id;
        }

        return $user->isAdmin()
            || $user->isProjectManager()
            || $user->isTeamMember();
    }

    /**
     * Only admin and project_manager can update fulfillment/payment status.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isProjectManager();
    }

    /**
     * Only admins can delete orders.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
