@extends('layouts.app')

@section('title', 'Inventory Dashboard — DJLN Marketing')
@section('page-title', '📦 Inventory Dashboard')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

{{-- ── GREETING + CTA ─────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        @php $h = now()->hour; $greet = $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening'); @endphp
        <h2 style="font-size:18px;font-weight:700;color:var(--text);">{{ $greet }}, {{ auth()->user()->name }} 👋</h2>
        <p style="font-size:12px;color:var(--muted);margin-top:2px;">{{ now()->format('l, F j, Y') }}</p>
    </div>
    {{-- New Order button hidden: customers place orders via /shop, admins manage fulfillment --}}
    @can('manage-orders')
        <a href="{{ route('orders.index') }}" class="btn btn-primary">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            View Orders
        </a>
    @endcan
</div>

{{-- ── REAL-TIME ORDER ALERTS ───────────────────────────────────── --}}
<div id="order-alert-widget" style="display:none;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:18px;">🔔</span>
            <span style="font-size:13px;font-weight:700;color:var(--text);">New Order Alerts</span>
            <span id="alert-count-badge" style="background:var(--red);color:white;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;">0</span>
        </div>
        <button onclick="markAllRead()" style="font-size:11px;color:var(--cyan);background:none;border:none;cursor:pointer;font-weight:600;">Mark all read ✓</button>
    </div>
    <div id="alert-list" style="display:flex;flex-direction:column;gap:8px;"></div>
</div>

{{-- ── KPI CARDS ───────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">

    <div class="kpi-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p class="kpi-label">Total Revenue</p>
            <span style="font-size:20px;">💰</span>
        </div>
        <p class="kpi-val" style="color:var(--cyan);">₱{{ number_format($totalRevenue, 0) }}</p>
        <p class="kpi-sub">
            <span style="color:var(--purple);">₱{{ number_format($wholesaleRevenue, 0) }}</span> wholesale ·
            <span style="color:var(--cyan);">₱{{ number_format($retailRevenue, 0) }}</span> retail
        </p>
    </div>

    <div class="kpi-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p class="kpi-label">This Month</p>
            <span style="font-size:20px;">📅</span>
        </div>
        <p class="kpi-val" style="color:var(--purple);">₱{{ number_format($monthlySales, 0) }}</p>
        <p class="kpi-sub">Today: <strong style="color:var(--text);">₱{{ number_format($todaySales, 0) }}</strong></p>
    </div>

    <div class="kpi-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p class="kpi-label">Active Orders</p>
            <span style="font-size:20px;">📋</span>
        </div>
        <p class="kpi-val" style="color:var(--amber);">{{ ($orderCounts->pending ?? 0) + ($orderCounts->processing ?? 0) }}</p>
        <p class="kpi-sub">
            <span style="color:var(--amber);">{{ $orderCounts->pending ?? 0 }}</span> pending ·
            <span style="color:var(--blue);">{{ $orderCounts->processing ?? 0 }}</span> processing
        </p>
    </div>

    <div class="kpi-card">
        @php $alertCount = ($stockStats->low_stock ?? 0) + ($stockStats->out_of_stock ?? 0); @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p class="kpi-label">Stock Alerts</p>
            <span style="font-size:20px;">{{ $alertCount > 0 ? '⚠️' : '✅' }}</span>
        </div>
        <p class="kpi-val" style="color:{{ $alertCount > 0 ? 'var(--red)' : 'var(--green)' }};">{{ $alertCount }}</p>
        <p class="kpi-sub">of <strong style="color:var(--text);">{{ $stockStats->total_products ?? 0 }}</strong> active SKUs need attention</p>
    </div>

</div>

{{-- ── REVENUE CHART + RESTOCK ────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start;">

    {{-- Chart — fixed height container prevents infinite stretching --}}
    <div class="card" style="padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div>
                <p style="font-size:14px;font-weight:600;color:var(--text);">Revenue Trend</p>
                <p style="font-size:11px;color:var(--muted);">Last 30 days · paid orders only</p>
            </div>
            <div style="display:flex;gap:16px;font-size:12px;">
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="width:10px;height:3px;background:var(--cyan);display:inline-block;border-radius:2px;"></span>
                    <span style="color:var(--text2);">Revenue</span>
                </span>
            </div>
        </div>
        {{-- CRITICAL: fixed height wrapper — chart cannot grow beyond this --}}
        <div style="position:relative;width:100%;height:240px;">
            <canvas id="revenueChart"></canvas>
        </div>
        <div style="display:flex;gap:20px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border);">
            <div>
                <p style="font-size:11px;color:var(--muted);">Wholesale</p>
                <p class="mono" style="font-size:13px;font-weight:600;color:var(--purple);">₱{{ number_format($wholesaleRevenue,2) }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:var(--muted);">Retail</p>
                <p class="mono" style="font-size:13px;font-weight:600;color:var(--cyan);">₱{{ number_format($retailRevenue,2) }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:var(--muted);">Delivered</p>
                <p class="mono" style="font-size:13px;font-weight:600;color:var(--green);">{{ $orderCounts->delivered ?? 0 }} orders</p>
            </div>
        </div>
    </div>

    {{-- Quick Restock --}}
    <div class="card" style="padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <p style="font-size:13px;font-weight:600;color:var(--text);">⚠️ Needs Restock</p>
            <a href="{{ route('products.index') }}?stock_status=low_stock" style="font-size:11px;color:var(--cyan);text-decoration:none;">View all</a>
        </div>

        @if($lowStockProducts->isEmpty())
        <div style="text-align:center;padding:24px 0;">
            <p style="font-size:28px;">✅</p>
            <p style="font-size:12px;color:var(--muted);margin-top:6px;">All items well stocked.</p>
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:14px;">
            @foreach($lowStockProducts->take(5) as $lsp)
            @php
                $pct = $lsp->reorder_level > 0
                    ? min(100, round(($lsp->stock_qty / ($lsp->reorder_level * 2)) * 100))
                    : 100;
                $barColor = $lsp->stock_qty === 0 ? 'var(--red)' : ($lsp->stock_qty <= $lsp->reorder_level ? 'var(--amber)' : 'var(--green)');
            @endphp
            <div x-data="{open:false}">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                    <div style="min-width:0;flex:1;margin-right:8px;">
                        <p style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $lsp->name }}</p>
                        <p class="mono" style="font-size:10px;color:var(--muted);">{{ $lsp->sku }}</p>
                    </div>
                    <span class="mono" style="font-size:11px;font-weight:700;color:{{ $barColor }};flex-shrink:0;">
                        {{ $lsp->stock_qty }}/{{ $lsp->reorder_level }}
                    </span>
                </div>
                <div class="stock-track" style="margin-bottom:6px;">
                    <div class="stock-fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                </div>
                <button @click="open=!open" class="btn btn-ghost" style="width:100%;justify-content:center;padding:4px;font-size:11px;">
                    + Restock
                </button>
                <div x-show="open" x-transition style="margin-top:6px;">
                    <form method="POST" action="{{ route('products.restock', $lsp->id) }}">
                        @csrf @method('PATCH')
                        <div style="display:flex;gap:6px;">
                            <input type="number" name="qty_received" min="1" placeholder="Qty"
                                   class="form-input" style="flex:1;padding:6px 8px;font-size:12px;">
                            <button type="submit" class="btn" style="padding:6px 12px;background:#f0fdf4;color:var(--green);border-color:#bbf7d0;font-size:12px;">✓</button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── CATEGORY HEALTH + TOP PRODUCTS ────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

    <div class="card" style="padding:20px;">
        <p class="section-label">Category Health</p>
        <div style="display:flex;flex-direction:column;gap:16px;">
            @foreach($categoryBreakdown as $cat)
            @php
                $total = $cat['product_count'];
                $healthPct = $total > 0 ? round(($cat['in_stock'] / $total) * 100) : 0;
                $slug = strtolower(str_replace([' ','&','/'],'-', $cat['name']));
                $cc = match(true) {
                    str_contains($slug,'cand')||str_contains($slug,'gumm') => ['class'=>'cat-candy',  'bar'=>'#b45309'],
                    str_contains($slug,'loll') => ['class'=>'cat-lolly',  'bar'=>'#6d28d9'],
                    str_contains($slug,'choc') => ['class'=>'cat-choco',  'bar'=>'#92400e'],
                    str_contains($slug,'ball') => ['class'=>'cat-balloon','bar'=>'#0369a1'],
                    str_contains($slug,'part') => ['class'=>'cat-party',  'bar'=>'#be185d'],
                    default                    => ['class'=>'cat-default','bar'=>'#64748b'],
                };
            @endphp
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:16px;">{{ $cat['icon'] ?? '📦' }}</span>
                        <span class="badge {{ $cc['class'] }}">{{ $cat['name'] }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;font-size:12px;">
                        <span style="color:var(--muted);">{{ $total }} SKUs</span>
                        <span class="mono" style="font-weight:600;color:var(--text);">₱{{ number_format($cat['retail_value'],0) }}</span>
                    </div>
                </div>
                <div class="stock-track">
                    <div class="stock-fill" style="width:{{ $healthPct }}%;background:{{ $cc['bar'] }};"></div>
                </div>
                <div style="display:flex;gap:10px;margin-top:4px;font-size:11px;">
                    <span style="color:var(--green);">{{ $cat['in_stock'] }} in stock</span>
                    @if($cat['low_stock']  > 0)<span style="color:var(--amber);">{{ $cat['low_stock'] }} low</span>@endif
                    @if($cat['out_of_stock'] > 0)<span style="color:var(--red);">{{ $cat['out_of_stock'] }} out</span>@endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card" style="padding:20px;">
        <p class="section-label">Top Products — This Month</p>
        @if($topProducts->isEmpty())
        <div style="text-align:center;padding:32px 0;color:var(--muted);">
            <p style="font-size:28px;">📊</p>
            <p style="font-size:12px;margin-top:8px;">No sales data this month.</p>
        </div>
        @else
        @php $maxRev = $topProducts->max('total_revenue'); @endphp
        <div style="display:flex;flex-direction:column;gap:14px;">
            @foreach($topProducts as $i => $tp)
            @php $pct = $maxRev > 0 ? round(($tp->total_revenue/$maxRev)*100) : 0; @endphp
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <span class="mono" style="font-size:11px;color:var(--muted);width:14px;flex-shrink:0;">{{ $i+1 }}</span>
                        <p style="font-size:12px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $tp->name }}</p>
                    </div>
                    <div style="text-align:right;flex-shrink:0;margin-left:10px;">
                        <p class="mono" style="font-size:12px;font-weight:700;color:var(--cyan);">₱{{ number_format($tp->total_revenue,0) }}</p>
                        <p style="font-size:10px;color:var(--muted);">{{ $tp->total_qty_sold }} sold</p>
                    </div>
                </div>
                <div class="stock-track">
                    <div class="stock-fill" style="width:{{ $pct }}%;background:linear-gradient(90deg,var(--cyan),var(--purple));"></div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── ORDER STATUS + RECENT ORDERS ──────────────────────────── --}}
<div style="display:grid;grid-template-columns:200px 1fr;gap:16px;align-items:start;">

    <div class="card" style="padding:20px;">
        <p class="section-label">Fulfillment</p>
        <div style="display:flex;flex-direction:column;gap:2px;">
            @foreach([
                ['Pending',    $orderCounts->pending    ?? 0, 'var(--amber)'],
                ['Processing', $orderCounts->processing ?? 0, 'var(--blue)'],
                ['Shipped',    $orderCounts->shipped    ?? 0, 'var(--purple)'],
                ['Delivered',  $orderCounts->delivered  ?? 0, 'var(--green)'],
                ['Cancelled',  $orderCounts->cancelled  ?? 0, 'var(--red)'],
            ] as [$label, $val, $color])
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="font-size:12px;color:var(--text2);">{{ $label }}</span>
                <span class="mono" style="font-size:14px;font-weight:700;color:{{ $color }};">{{ $val }}</span>
            </div>
            @endforeach
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
            <span class="badge badge-purple">{{ $orderCounts->wholesale_count ?? 0 }} wholesale</span>
            <span class="badge badge-cyan">{{ $orderCounts->retail_count ?? 0 }} retail</span>
        </div>
    </div>

    <div class="card" style="overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:14px;font-weight:600;color:var(--text);">Recent Orders</p>
            <a href="{{ route('orders.index') }}" style="font-size:12px;color:var(--cyan);text-decoration:none;">View all →</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th style="text-align:right;">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                @php
                    $fc = match($order->fulfillment_status) {
                        'pending'    => 'badge-amber',
                        'processing' => 'badge-blue',
                        'shipped'    => 'badge-purple',
                        'delivered'  => 'badge-green',
                        'cancelled'  => 'badge-red',
                        default      => 'badge-muted',
                    };
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="mono" style="font-size:12px;color:var(--cyan);text-decoration:none;font-weight:600;">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td style="font-size:13px;">{{ $order->customer->name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $order->order_type === 'wholesale' ? 'badge-purple' : 'badge-cyan' }}">
                            {{ ucfirst($order->order_type) }}
                        </span>
                    </td>
                    <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:var(--text);">
                        ₱{{ number_format($order->total_amount,2) }}
                    </td>
                    <td><span class="badge {{ $fc }}">{{ ucfirst($order->fulfillment_status) }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">No orders yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── INVENTORY VALUE FOOTER ──────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
    @foreach([
        ['Total SKUs',       ($stockStats->total_products ?? 0).' products',                          'var(--text)',   '📦'],
        ['Retail Value',     '₱'.number_format($stockStats->total_inventory_value ?? 0,2),            'var(--cyan)',   '💵'],
        ['Cost Basis',       '₱'.number_format($stockStats->total_cost_value ?? 0,2),                 'var(--purple)', '📊'],
        ['In Stock',         ($stockStats->in_stock ?? 0).' SKUs',                                    'var(--green)',  '✅'],
    ] as [$label,$val,$color,$icon])
    <div class="card" style="padding:16px 20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
            <span style="font-size:16px;">{{ $icon }}</span>
            <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);">{{ $label }}</p>
        </div>
        <p class="mono" style="font-size:18px;font-weight:700;color:{{ $color }};">{{ $val }}</p>
    </div>
    @endforeach
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Revenue Chart ───────────────────────────────────────────────
(function () {
    const labels = [], data = [];
    for (let i = 29; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        labels.push(d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }));
        data.push(0);
    }
    const raw = @json($chartData->map(fn($d) => [
        'date'  => \Carbon\Carbon::parse($d->date)->format('M j'),
        'total' => (float) $d->total,
    ]));
    raw.forEach(o => {
        const idx = labels.indexOf(o.date);
        if (idx >= 0) data[idx] += o.total;
    });
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Revenue (₱)',
                data,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,0.07)',
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#0ea5e9',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    titleColor: '#94a3b8',
                    bodyColor: '#0f172a',
                    bodyFont: { family: 'JetBrains Mono', weight: '600' },
                    callbacks: {
                        label: ctx => ' ₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { color: '#94a3b8', maxTicksLimit: 8, font: { size: 10 } }
                },
                y: {
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 },
                        callback: v => '₱' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v)
                    }
                }
            }
        }
    });
})();

// ── Order Alert Widget ──────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function fetchAlerts() {
    try {
        const res  = await fetch('{{ route('notifications.recent') }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        const unread = data.filter(n => !n.read);

        const widget = document.getElementById('order-alert-widget');
        const list   = document.getElementById('alert-list');
        const badge  = document.getElementById('alert-count-badge');

        if (unread.length === 0) { widget.style.display = 'none'; return; }

        badge.textContent   = unread.length;
        widget.style.display = 'block';
        list.innerHTML = '';

        unread.forEach(n => {
            const d   = n.data || {};
            const msg = d.message || ('New Order ' + (d.order_number || ''));
            const el  = document.createElement('div');
            el.style.cssText = `
                display:flex;align-items:center;justify-content:space-between;
                padding:12px 16px;background:#f0fdf4;
                border:1px solid #86efac;border-radius:10px;
                animation:slideIn .25s ease;
            `;
            el.innerHTML = `
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:20px;">${d.icon || '🛒'}</span>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#15803d;">${msg}</p>
                        <p style="font-size:11px;color:#86efac;">${n.created_at}</p>
                    </div>
                </div>
                <button onclick="markRead('${n.id}', this.parentElement)"
                    style="font-size:11px;background:#dcfce7;border:1px solid #86efac;
                           color:#15803d;padding:4px 10px;border-radius:6px;cursor:pointer;
                           font-weight:600;">✓ Read</button>
            `;
            list.appendChild(el);
        });
    } catch(e) { /* silent — network failure */ }
}

async function markRead(id, el) {
    try {
        await fetch(`/notifications/${id}/read`, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        el.remove();
        await fetchAlerts(); // refresh count
    } catch(e) {}
}

async function markAllRead() {
    try {
        await fetch('{{ route('notifications.mark-all-read') }}', {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        document.getElementById('order-alert-widget').style.display = 'none';
    } catch(e) {}
}

// Slide-in animation
const style = document.createElement('style');
style.textContent = `@keyframes slideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }`;
document.head.appendChild(style);

// Load on page open, then poll every 30 seconds
fetchAlerts();
setInterval(fetchAlerts, 30000);
</script>
@endpush
