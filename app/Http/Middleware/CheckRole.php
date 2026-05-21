<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Allows role-based access control on routes.
     * Usage: Route::middleware('checkRole:admin,project_manager')->group(...)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // If user is not authenticated, redirect to login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = strtolower(trim((string) auth()->user()->role));
        $roles    = array_map(fn ($r) => strtolower(trim((string) $r)), $roles);

        // Check if user's role is in allowed roles
        if (!in_array($userRole, $roles, true)) {
            // Customers get a friendly redirect instead of a hard 403
            if ($userRole === 'client') {
                return redirect()
                    ->route('customer.dashboard')
                    ->with('error', 'That area is for staff only. Here\'s your shop instead!');
            }

            abort(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
