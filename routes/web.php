<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryDashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;

/*
|--------------------------------------------------------------------------
| DJLN Marketing — Wholesale & Retail Inventory System
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Public ──────────────────────────────────────────────────────────────
Route::get('/', [PageController::class, 'landing'])->name('home');

// ── Auth ─────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'handleLogin'])->middleware('throttle:5,1');
});

// ── Protected ─────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // ── Logout (available to all roles) ──────────────────────────────────
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Entry point — role-aware redirect (customers → /shop, staff → /inventory)
    Route::get('/home', function () {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isProjectManager() || $user->isTeamMember()) {
            return redirect()->route('inventory.dashboard');
        }
        return redirect()->route('customer.dashboard');
    })->name('home.index');

    // ══════════════════════════════════════════════════════════════════════
    // CUSTOMER SHOP — role=client only
    // customerGuard on admin routes below ensures customers never sneak in
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('shop')->name('customer.')->group(function () {
        Route::get('/',               [CustomerController::class, 'dashboard'])->name('dashboard');
        Route::get('/catalog',        [CustomerController::class, 'catalog'])->name('catalog');
        Route::get('/cart',           [CustomerController::class, 'cart'])->name('cart');
        Route::post('/cart/add',      [CustomerController::class, 'addToCart'])->name('cart.add');
        Route::post('/cart/remove',   [CustomerController::class, 'removeFromCart'])->name('cart.remove');
        Route::post('/order',         [CustomerController::class, 'placeOrder'])->name('order.place');
        Route::get('/orders/{order}', [CustomerController::class, 'orderConfirmation'])->name('order-confirmation');
    });

    // ══════════════════════════════════════════════════════════════════════
    // NOTIFICATIONS — accessible to ALL roles (staff & clients)
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count',   [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/recent',         [NotificationController::class, 'recent'])->name('recent');
        Route::put('/mark-all-read',  [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/clear-all',   [NotificationController::class, 'destroyAll'])->name('clear-all');
        Route::put('/{id}/read',      [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::delete('/{id}',        [NotificationController::class, 'destroy'])->name('destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // ADMIN / STAFF AREA — customerGuard blocks role=client from all below
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('customerGuard')->group(function () {

        // ── Inventory Dashboard ───────────────────────────────────────────
        Route::get('/inventory', [InventoryDashboardController::class, 'index'])
            ->name('inventory.dashboard');

        // ── Categories ────────────────────────────────────────────────────
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');

            Route::middleware('checkRole:admin,project_manager')->group(function () {
                Route::get('/create',  [CategoryController::class, 'create'])->name('create');
                Route::post('/',       [CategoryController::class, 'store'])->name('store');
            });

            Route::get('/{category}', [CategoryController::class, 'show'])->name('show');

            Route::middleware('checkRole:admin,project_manager')->group(function () {
                Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
                Route::put('/{category}',      [CategoryController::class, 'update'])->name('update');
                Route::delete('/{category}',   [CategoryController::class, 'destroy'])->name('destroy');
            });
        });

        // ── Products ──────────────────────────────────────────────────────
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');

            Route::middleware('checkRole:admin,project_manager')->group(function () {
                Route::get('/create',  [ProductController::class, 'create'])->name('create');
                Route::post('/',       [ProductController::class, 'store'])->name('store');
            });

            Route::get('/{product}', [ProductController::class, 'show'])->name('show');

            Route::middleware('checkRole:admin,project_manager')->group(function () {
                Route::get('/{product}/edit',      [ProductController::class, 'edit'])->name('edit');
                Route::put('/{product}',           [ProductController::class, 'update'])->name('update');
                Route::delete('/{product}',        [ProductController::class, 'destroy'])->name('destroy');
                Route::patch('/{product}/restock', [ProductController::class, 'restock'])->name('restock');
            });
        });

        // ── Orders ────────────────────────────────────────────────────────
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');

            // ⚠️ Static routes MUST come before {order} wildcard
            Route::middleware('checkRole:admin,project_manager')->group(function () {
                Route::get('/create', [OrderController::class, 'create'])->name('create');
                Route::post('/',      [OrderController::class, 'store'])->name('store');
            });

            Route::get('/{order}', [OrderController::class, 'show'])->name('show');

            Route::middleware('checkRole:admin,project_manager')->group(function () {
                Route::delete('/{order}',       [OrderController::class, 'destroy'])->name('destroy');
                Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
            });
        });

        // ── User Management (admin only) ──────────────────────────────────
        Route::middleware('checkRole:admin')->group(function () {
            Route::resource('users', UserController::class);
            Route::get('/audit-logs', [UserController::class, 'auditLogs'])->name('audit-logs');
        });

        // ── Notifications (staff-only API variants, kept for admin panel) ─────
        // Full notification routes are accessible to all roles above.

        // ── Profile ───────────────────────────────────────────────────────
        Route::get('/profile/preferences',    [PageController::class, 'preferences'])->name('profile.preferences');
        Route::patch('/profile/update-theme', [PageController::class, 'updateTheme'])->name('profile.update-theme');

    }); // end customerGuard group

}); // end auth group
