{{-- ══════════════ SIDEBAR ══════════════ --}}
<aside class="sidebar" id="djln-sidebar">

    {{-- Logo --}}
    <div style="height:60px; border-bottom:1px solid var(--border); display:flex; align-items:center; padding:0 14px; gap:12px; overflow:hidden; flex-shrink:0;">
        <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#0ea5e9,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:17px;">🎉</div>
        <div style="overflow:hidden;">
            <p class="nav-label" style="font-size:13px;font-weight:700;color:var(--text);opacity:0;">DJLN Marketing</p>
            <p class="nav-label" style="font-size:10px;color:var(--muted);opacity:0;margin-top:1px;">Wholesale System</p>
        </div>
    </div>

    {{-- Toggle --}}
    <button onclick="sidebarToggle()"
        style="position:absolute;top:18px;right:-11px;width:22px;height:22px;background:var(--surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;color:var(--muted);transition:background 0.15s;"
        onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background='var(--surface)'">
        <svg id="toggle-icon" style="width:10px;height:10px;transition:transform 0.25s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    {{-- Navigation --}}
    <nav style="flex:1; padding:10px 0; overflow:hidden; overflow-y:auto;">
        <div class="nav-section">Main</div>

        <a href="{{ route('inventory.dashboard') }}"
           class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="nav-label">Dashboard</span>
        </a>

        <div class="nav-section">Catalogue</div>

        <a href="{{ route('categories.index') }}"
           class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span class="nav-label">Categories</span>
        </a>

        <a href="{{ route('products.index') }}"
           class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span class="nav-label">Products</span>
        </a>

        <div class="nav-section">Sales</div>

        <a href="{{ route('orders.index') }}"
           class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="nav-label">Orders</span>
        </a>

        @if(auth()->check() && auth()->user()->isAdmin())
        <div class="nav-section">Admin</div>
        <a href="{{ route('users.index') }}"
           class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="nav-label">Users</span>
        </a>
        @endif
    </nav>

    {{-- User area --}}
    @if(auth()->check())
    <div style="border-top:1px solid var(--border); padding:10px 8px; flex-shrink:0;">
        <div style="position:relative;" x-data="{open:false}" @click.away="open=false">
            <button @click="open=!open" class="nav-item" style="width:100%;border-left:none;border-radius:7px;">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0ea5e9,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;flex-shrink:0;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="nav-label" style="text-align:left;line-height:1.3;">
                    <p style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">{{ auth()->user()->name }}</p>
                    <p style="font-size:10px;color:var(--muted);text-transform:capitalize;">{{ auth()->user()->role }}</p>
                </div>
            </button>
            <div x-show="open" x-transition
                 style="position:absolute;bottom:100%;left:0;right:0;background:var(--surface);border:1px solid var(--border);border-radius:8px;overflow:hidden;z-index:50;min-width:160px;margin-bottom:4px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
                <a href="{{ route('profile.preferences') }}" style="display:block;padding:10px 14px;font-size:13px;color:var(--text2);transition:all 0.1s;" onmouseover="this.style.background='var(--surface2)';this.style.color='var(--text)'" onmouseout="this.style.background='';this.style.color='var(--text2)'">
                    ⚙️ Preferences
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="display:block;width:100%;text-align:left;padding:10px 14px;font-size:13px;color:var(--red);border-top:1px solid var(--border);background:transparent;cursor:pointer;transition:background 0.1s;" onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background=''">
                        🚪 Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</aside>
