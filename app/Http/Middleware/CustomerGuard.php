<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CustomerGuard Middleware
 *
 * Prevents authenticated customers (role = 'client') from accessing
 * any admin/staff routes (inventory, products, orders management, users, etc.)
 * and silently redirects them to their shop dashboard.
 *
 * Usage: Route::middleware('customerGuard')->group(...)
 * Applied to: all routes EXCEPT /shop/*
 */
class CustomerGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $role = strtolower(trim((string) auth()->user()->role));

            // Customers must stay in the /shop area
            if ($role === 'client') {
                return redirect()
                    ->route('customer.dashboard')
                    ->with('error', 'Access restricted. Redirecting you to your shop dashboard.');
            }
        }

        return $next($request);
    }
}
