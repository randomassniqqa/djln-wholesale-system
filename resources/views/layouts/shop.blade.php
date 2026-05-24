<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DJLN Shop — Wholesale Marketplace')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
        }

        /* ── Thematic Watermark Background ── */
        /* Fixed party pattern at 5% opacity — subtle, never distracts */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: 0.045;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Ctext x='20'  y='50'  font-size='32'%3E%F0%9F%8E%88%3C/text%3E%3Ctext x='110' y='40'  font-size='24'%3E%F0%9F%8D%AC%3C/text%3E%3Ctext x='60'  y='110' font-size='28'%3E%F0%9F%8E%89%3C/text%3E%3Ctext x='150' y='120' font-size='22'%3E%F0%9F%8D%AD%3C/text%3E%3Ctext x='10'  y='170' font-size='26'%3E%F0%9F%8E%80%3C/text%3E%3Ctext x='100' y='185' font-size='20'%3E%F0%9F%8E%88%3C/text%3E%3Ctext x='160' y='175' font-size='28'%3E%F0%9F%8D%AC%3C/text%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 200px 200px;
        }

        /* Ensure page content always sits above the watermark */
        .shop-nav, .shop-page { position: relative; z-index: 1; }

        /* ── Shop Navbar ── */
        .shop-nav {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .shop-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .shop-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .shop-brand-logo {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(14,165,233,0.3);
        }
        .shop-brand-name {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .shop-brand-tagline {
            font-size: 10px;
            color: #94a3b8;
            font-weight: 500;
        }
        .nav-links {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        .nav-link {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            transition: all 0.15s;
        }
        .nav-link:hover { background: #f1f5f9; color: #0f172a; }
        .nav-link.active { background: #f0f9ff; color: #0ea5e9; font-weight: 600; }
        .nav-cart-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #0ea5e9;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(14,165,233,0.3);
        }
        .nav-cart-btn:hover { background: #0284c7; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14,165,233,0.4); }
        .cart-badge {
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 99px;
            min-width: 18px;
            text-align: center;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            cursor: default;
        }
        .nav-user-avatar {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }
        .nav-user-name { font-size: 13px; font-weight: 600; color: #0f172a; }
        .nav-user-role { font-size: 10px; color: #94a3b8; }

        /* ── Page wrapper ── */
        .shop-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* ── Alerts ── */
        .shop-alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            border: 1px solid;
        }
        .shop-alert-success { background: #f0fdf4; color: #15803d; border-color: #86efac; }
        .shop-alert-error   { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }

        /* ── Buttons ── */
        .btn-shop-primary {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: white; border: none;
            padding: 11px 24px; border-radius: 12px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(14,165,233,0.3);
        }
        .btn-shop-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14,165,233,0.4); }
        .btn-shop-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            background: #fff; color: #475569; border: 1.5px solid #e2e8f0;
            padding: 10px 20px; border-radius: 12px;
            font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none;
            transition: all 0.15s;
        }
        .btn-shop-secondary:hover { border-color: #0ea5e9; color: #0ea5e9; background: #f0f9ff; }
        .btn-danger-soft {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5;
            padding: 7px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 500;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-danger-soft:hover { background: #fee2e2; }

        /* ── Product Card ── */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.25s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
            border-color: #bae6fd;
        }
        .product-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 48px;
        }
        .product-body { padding: 16px; }
        .product-category-tag {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.06em; color: #0ea5e9;
            background: #f0f9ff; border: 1px solid #bae6fd;
            padding: 2px 8px; border-radius: 99px; margin-bottom: 8px;
        }
        .product-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .product-unit {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 12px;
        }
        .product-price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 6px;
        }
        .product-price {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .product-price-label {
            font-size: 11px;
            color: #94a3b8;
        }
        .product-moq {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 14px;
            background: #f8fafc;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        .stock-tag {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 600;
            padding: 3px 10px; border-radius: 99px;
        }
        .stock-available { background: #f0fdf4; color: #16a34a; }
        .stock-out       { background: #fef2f2; color: #dc2626; }
        .product-add-btn {
            width: 100%;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: white; border: none;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px; font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 12px;
        }
        .product-add-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .product-add-btn:disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; transform: none; }
        .qty-input {
            width: 60px;
            padding: 6px 8px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px; font-weight: 600;
            text-align: center;
            outline: none;
            transition: border-color 0.15s;
        }
        .qty-input:focus { border-color: #0ea5e9; }

        /* ── Category filter pills ── */
        .cat-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
        .cat-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: 99px;
            font-size: 13px; font-weight: 500;
            border: 1.5px solid #e2e8f0;
            background: #fff; color: #475569;
            text-decoration: none; cursor: pointer;
            transition: all 0.15s;
        }
        .cat-pill:hover { border-color: #0ea5e9; color: #0ea5e9; background: #f0f9ff; }
        .cat-pill.active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; box-shadow: 0 4px 12px rgba(14,165,233,0.3); }

        /* ── Welcome banner ── */
        .welcome-banner {
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            border-radius: 20px;
            padding: 32px 36px;
            color: white;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        .welcome-banner::after {
            content: '🛍️';
            position: absolute; right: 32px; top: 50%;
            transform: translateY(-50%);
            font-size: 80px; opacity: 0.2;
        }
        .welcome-title { font-size: 26px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.02em; }
        .welcome-sub   { font-size: 14px; opacity: 0.85; }

        /* ── Status tracker ── */
        .status-track {
            display: flex;
            gap: 0;
            align-items: center;
            margin: 16px 0;
        }
        .status-step {
            display: flex; flex-direction: column; align-items: center; gap: 4px;
            flex: 1;
        }
        .status-dot {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            position: relative;
            z-index: 1;
        }
        .status-dot.done { background: #0ea5e9; border-color: #0ea5e9; color: white; }
        .status-dot.active { background: #fff; border-color: #0ea5e9; color: #0ea5e9; font-weight: 700; }
        .status-label { font-size: 10px; font-weight: 600; color: #94a3b8; text-align: center; }
        .status-label.done   { color: #0ea5e9; }
        .status-label.active { color: #0f172a; }
        .status-line {
            flex: 1; height: 2px; background: #e2e8f0; margin-top: -16px;
        }
        .status-line.done { background: #0ea5e9; }

        /* ── Section title ── */
        .section-title {
            font-size: 18px; font-weight: 700; color: #0f172a;
            margin-bottom: 16px; letter-spacing: -0.01em;
        }

        /* ── Panel ── */
        .shop-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        /* ── Cart table ── */
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th {
            text-align: left; padding: 10px 16px;
            font-size: 10px; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: #94a3b8;
            background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        }
        .cart-table td {
            padding: 16px; border-bottom: 1px solid #f1f5f9;
            vertical-align: middle; color: #475569; font-size: 14px;
        }
        .cart-table tr:last-child td { border-bottom: none; }

        /* ── Order status badge ── */
        .order-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 99px;
            font-size: 12px; font-weight: 600; border: 1px solid;
        }
        .badge-pending    { background: #fffbeb; color: #d97706; border-color: #fde68a; }
        .badge-processing { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
        .badge-shipped    { background: #f5f3ff; color: #8b5cf6; border-color: #ddd6fe; }
        .badge-delivered  { background: #f0fdf4; color: #16a34a; border-color: #86efac; }
        .badge-cancelled  { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }

        /* ── Confetti animation for confirmation ── */
        @keyframes float-up {
            0%   { transform: translateY(0) rotate(0); opacity: 1; }
            100% { transform: translateY(-80px) rotate(180deg); opacity: 0; }
        }
        .confetti-dot {
            position: absolute;
            width: 8px; height: 8px;
            border-radius: 50%;
            animation: float-up 1.5s ease-out forwards;
        }

        /* ── Search bar ── */
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }
        .search-input {
            flex: 1;
            padding: 10px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            background: white;
        }
        .search-input:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.1); }
        .search-btn {
            padding: 10px 20px;
            background: #0ea5e9; color: white; border: none;
            border-radius: 12px; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.15s;
        }
        .search-btn:hover { background: #0284c7; }

        @media (max-width: 768px) {
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
            .shop-page { padding: 20px 16px; }
            .welcome-banner { padding: 24px; }
            .welcome-title { font-size: 20px; }
        }
    </style>
</head>
<body>

{{-- ══════════════ SHOP NAVBAR ══════════════ --}}
<nav class="shop-nav">
    <div class="shop-nav-inner">
        {{-- Brand --}}
        <a href="{{ route('customer.dashboard') }}" class="shop-brand">
            <div class="shop-brand-logo">🛍️</div>
            <div>
                <div class="shop-brand-name">DJLN Shop</div>
                <div class="shop-brand-tagline">Wholesale Marketplace</div>
            </div>
        </a>

        {{-- Navigation links --}}
        <div class="nav-links">
            <a href="{{ route('customer.dashboard') }}"
               class="nav-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
                🏠 Home
            </a>
            <a href="{{ route('customer.catalog') }}"
               class="nav-link {{ request()->routeIs('customer.catalog') ? 'active' : '' }}">
                🛒 Shop
            </a>
        </div>

        {{-- Cart + User --}}
        <div style="display:flex;align-items:center;gap:12px;">
            @php $cartCount = count(session()->get('djln_cart', [])); @endphp
            <a href="{{ route('customer.cart') }}" class="nav-cart-btn">
                🛒 Cart
                @if($cartCount > 0)
                    <span class="cart-badge">{{ $cartCount }}</span>
                @endif
            </a>

            {{-- Notification Bell --}}
            <a href="{{ route('notifications.index') }}"
               id="shop-notif-btn"
               style="position:relative;display:flex;align-items:center;justify-content:center;
                      width:38px;height:38px;border-radius:10px;border:1.5px solid #e2e8f0;
                      background:#fff;color:#475569;text-decoration:none;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#0ea5e9';this.style.color='#0ea5e9';this.style.background='#f0f9ff';"
               onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';this.style.background='#fff';">
                <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span id="shop-notif-dot"
                      style="display:none;position:absolute;top:4px;right:4px;width:8px;height:8px;
                             background:#dc2626;border-radius:50%;border:2px solid #fff;"></span>
            </a>

            <div class="nav-user">
                <div class="nav-user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <div class="nav-user-name">{{ auth()->user()->name }}</div>
                    <div class="nav-user-role">Customer</div>
                </div>
            </div>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" style="background:none;border:none;color:#94a3b8;font-size:12px;cursor:pointer;padding:6px 10px;border-radius:8px;transition:all 0.15s;"
                    onmouseover="this.style.background='#fef2f2';this.style.color='#dc2626';"
                    onmouseout="this.style.background='none';this.style.color='#94a3b8';">
                    Sign out
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ══════════════ PAGE CONTENT ══════════════ --}}
<div class="shop-page">

    @if(session('success'))
        <div class="shop-alert shop-alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="shop-alert shop-alert-error">❌ {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="shop-alert shop-alert-error">
            <strong>Please fix the following:</strong>
            <ul style="margin-top:6px;padding-left:16px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</div>

@stack('scripts')

<script>
// Shop notification bell dot polling
@auth
(function() {
    const dot = document.getElementById('shop-notif-dot');
    if (!dot) return;
    function refresh() {
        fetch('{{ route("notifications.unread-count") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            dot.style.display = (data.count > 0) ? 'block' : 'none';
        })
        .catch(() => {});
    }
    refresh();
    setInterval(refresh, 60000);
})();
@endauth
</script>
</body>
</html>
