<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DJLN Marketing — Wholesale System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (function() {
            const theme = "{{ auth()->check() ? auth()->user()->theme_preference : 'system' }}";
            if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        /* ── Design Tokens ── */
        :root {
            --bg:       #f1f5f9;   /* slate-100 */
            --surface:  #ffffff;
            --surface2: #f8fafc;   /* slate-50 */
            --border:   #e2e8f0;   /* slate-200 */
            --border2:  #cbd5e1;   /* slate-300 */
            --sidebar:  #ffffff;   /* white */
            --sid-text: #475569;   /* slate-600 */
            --sid-act:  #0ea5e9;   /* cyan-500 */

            --text:     #0f172a;   /* slate-900 */
            --text2:    #475569;   /* slate-600 */
            --muted:    #94a3b8;   /* slate-400 */

            --cyan:     #0ea5e9;
            --blue:     #3b82f6;
            --purple:   #8b5cf6;
            --green:    #16a34a;   /* green-700 — high contrast on white */
            --amber:    #d97706;   /* amber-600 */
            --red:      #dc2626;
            --pink:     #db2777;
        }

        html.dark {
            --bg:       #020617;   /* slate-950 */
            --surface:  #0f172a;   /* slate-900 */
            --surface2: #1e293b;   /* slate-800 */
            --border:   #334155;   /* slate-700 */
            --border2:  #475569;   /* slate-600 */
            --sidebar:  #0f172a;   /* slate-900 */
            --sid-text: #94a3b8;   /* slate-400 */
            --sid-act:  #38bdf8;   /* sky-400 */

            --text:     #f8fafc;   /* slate-50 */
            --text2:    #cbd5e1;   /* slate-300 */
            --muted:    #64748b;   /* slate-500 */
            
            --green:    #22c55e;   /* green-500 — high contrast on dark */
        }

        * { font-family: 'Inter', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        body { background: var(--bg); color: var(--text); }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 4px; }

        /* ── Sidebar ── */
        .sidebar {
            width: 64px;
            min-height: 100vh;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: width 0.25s cubic-bezier(0.4,0,0.2,1);
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .sidebar.open { width: 224px; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px;
            height: 42px;
            color: var(--sid-text);
            border-left: 2px solid transparent;
            border-radius: 0 6px 6px 0;
            text-decoration: none;
            transition: all 0.15s;
            white-space: nowrap;
            overflow: hidden;
            margin: 2px 6px;
        }
        .nav-item:hover  { color: var(--text); background: var(--surface2); border-left-color: var(--sid-act); }
        .nav-item.active { color: var(--sid-act); background: rgba(14,165,233,0.08); border-left-color: var(--sid-act); font-weight: 600; }
        .nav-icon  { flex-shrink: 0; width: 18px; height: 18px; }
        .nav-label { font-size: 13px; font-weight: 500; opacity: 0; transition: opacity 0.1s 0.06s; }
        .sidebar.open .nav-label { opacity: 1; }

        .nav-section {
            font-size: 9px; font-weight: 700; letter-spacing: 0.12em;
            text-transform: uppercase; color: var(--muted); padding: 14px 16px 4px;
            white-space: nowrap; overflow: hidden; opacity: 0; transition: opacity 0.1s;
        }
        .sidebar.open .nav-section { opacity: 1; }

        /* ── Cards ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        }
        .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

        /* ── KPI Card ── */
        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            transition: box-shadow 0.2s;
        }
        .kpi-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .kpi-val   { font-family: 'JetBrains Mono',monospace; font-size: 1.85rem; font-weight: 700; line-height: 1; }
        .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); margin-bottom: 6px; }
        .kpi-sub   { font-size: 12px; margin-top: 8px; color: var(--text2); }

        /* ── Badges ── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 9px; border-radius: 99px;
            font-size: 11px; font-weight: 600; border: 1px solid;
        }
        .badge-green  { background: #f0fdf4; color: var(--green);  border-color: #bbf7d0; }
        .badge-amber  { background: #fffbeb; color: var(--amber);  border-color: #fde68a; }
        .badge-red    { background: #fef2f2; color: var(--red);    border-color: #fecaca; }
        .badge-blue   { background: #eff6ff; color: var(--blue);   border-color: #bfdbfe; }
        .badge-purple { background: #f5f3ff; color: var(--purple); border-color: #ddd6fe; }
        .badge-pink   { background: #fdf2f8; color: var(--pink);   border-color: #fbcfe8; }
        .badge-muted  { background: #f8fafc; color: var(--muted);  border-color: var(--border); }
        .badge-cyan   { background: #f0f9ff; color: var(--cyan);   border-color: #bae6fd; }

        /* ── Category palette ── */
        .cat-candy  { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .cat-balloon{ background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
        .cat-party  { background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; }
        .cat-choco  { background: #fff7ed; color: #92400e; border: 1px solid #fed7aa; }
        .cat-lolly  { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
        .cat-default{ background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: 7px; font-size: 13px;
            font-weight: 500; border: 1px solid; transition: all 0.15s;
            cursor: pointer; text-decoration: none;
        }
        .btn-primary { background: var(--cyan);  color: #fff; border-color: var(--cyan); }
        .btn-primary:hover { background: #0284c7; border-color: #0284c7; }
        .btn-ghost   { background: var(--surface); color: var(--text2); border-color: var(--border); }
        .btn-ghost:hover { background: var(--surface2); border-color: var(--border2); }
        .btn-danger  { background: #fef2f2; color: var(--red); border-color: #fecaca; }
        .btn-danger:hover { background: #fee2e2; }
        .btn-amber   { background: #fffbeb; color: var(--amber); border-color: #fde68a; }

        /* ── Stock progress bar ── */
        .stock-track { background: var(--border); border-radius: 4px; height: 5px; overflow: hidden; }
        .stock-fill  { height: 5px; border-radius: 4px; transition: width 0.5s ease; }

        /* ── Margin pill ── */
        .margin-pill {
            display: inline-flex; align-items: center; gap: 3px;
            padding: 1px 7px; border-radius: 99px; font-size: 10px;
            font-weight: 700; font-family: 'JetBrains Mono',monospace; border: 1px solid;
        }

        /* ── Data table ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th {
            text-align: left; padding: 10px 16px;
            font-size: 10px; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: var(--muted);
            background: var(--surface2); border-bottom: 1px solid var(--border);
        }
        .data-table td {
            padding: 11px 16px; border-bottom: 1px solid var(--border);
            vertical-align: middle; color: var(--text2);
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: var(--surface2); }

        /* ── Form controls ── */
        .form-input {
            width: 100%; padding: 8px 12px; font-size: 13px;
            color: var(--text); background: var(--surface);
            border: 1px solid var(--border); border-radius: 7px;
            outline: none; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
        }
        .form-label {
            display: block; font-size: 12px; font-weight: 600;
            color: var(--text2); margin-bottom: 5px;
        }

        /* ── Alerts ── */
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; border: 1px solid; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .alert-error   { background: #fef2f2; color: var(--red); border-color: #fecaca; }
        .alert-warning { background: #fffbeb; color: var(--amber); border-color: #fde68a; }

        /* ── Toast ── */
        #djln-toast {
            position: fixed; bottom: 24px; right: 24px; z-index: 99999;
            padding: 12px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
            background: #fff; border: 1px solid #bbf7d0; color: #15803d;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(60px); opacity: 0;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1); pointer-events: none;
        }
        #djln-toast.show { transform: translateY(0); opacity: 1; }

        /* Section header label */
        .section-label {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.1em; color: var(--muted); margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div style="display:flex; min-height:100vh;">

    {{-- ══════════════ SIDEBAR ══════════════ --}}
    @include('layouts.navigation')

    {{-- ══════════════ MAIN ══════════════ --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;">

        {{-- Top Bar --}}
        <header style="background:var(--surface);border-bottom:1px solid var(--border);padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h1 style="font-size:15px;font-weight:600;color:var(--text);">@yield('page-title', 'DJLN Marketing')</h1>
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="text-align:right;">
                    <p style="font-size:13px;font-weight:500;color:var(--text);">{{ auth()->user()->name }}</p>
                    <p style="font-size:11px;color:var(--muted);text-transform:capitalize;">{{ auth()->user()->role }}</p>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main style="flex:1;overflow:auto;background:var(--bg);padding:24px;">

            @if(session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">❌ {{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <p style="font-weight:600;margin-bottom:6px;">Please fix the following:</p>
                    <ul style="list-style:disc;padding-left:16px;margin:0;">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

{{-- Toast --}}
<div id="djln-toast"></div>

<script>
// ── Sidebar toggle (no Alpine dependency) ──────────────────────
function sidebarToggle() {
    const s    = document.getElementById('djln-sidebar');
    const icon = document.getElementById('toggle-icon');
    const open = s.classList.toggle('open');
    localStorage.setItem('djln_sidebar', open ? '1' : '0');
    icon.style.transform = open ? 'rotate(180deg)' : '';
    s.querySelectorAll('.nav-label').forEach(el => el.style.opacity = open ? '1' : '0');
}

// Restore sidebar state on load
(function() {
    if (localStorage.getItem('djln_sidebar') === '1') {
        const s = document.getElementById('djln-sidebar');
        s.classList.add('open');
        document.getElementById('toggle-icon').style.transform = 'rotate(180deg)';
        s.querySelectorAll('.nav-label').forEach(el => el.style.opacity = '1');
    }
})();

// ── Global toast ───────────────────────────────────────────────
function djlnToast(msg, type = 'success') {
    const t = document.getElementById('djln-toast');
    if (type === 'success') {
        t.style.borderColor = '#bbf7d0'; t.style.color = '#15803d';
    } else {
        t.style.borderColor = '#fecaca'; t.style.color = '#dc2626';
    }
    t.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}

@if(session('success'))
    document.addEventListener('DOMContentLoaded', () => djlnToast('{{ addslashes(session("success")) }}'));
@endif
</script>

@stack('scripts')
</body>
</html>
