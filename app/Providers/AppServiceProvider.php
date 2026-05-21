<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Models\Order;
use App\Policies\OrderPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // ── Order Policy Registration ─────────────────────────────────
        // Maps Order model actions to OrderPolicy gates.
        // Used in controllers via $this->authorize() and in Blade via @can.
        Gate::policy(Order::class, OrderPolicy::class);

        // Convenience gate: true for any staff member (not client)
        Gate::define('manage-orders', function ($user) {
            return $user->isAdmin()
                || $user->isProjectManager()
                || $user->isTeamMember();
        });
    }
}
