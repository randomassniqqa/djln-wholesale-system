@extends('layouts.app')
@section('title', 'Notifications — DJLN Marketing')
@section('page-title', '🔔 Notifications')

@section('content')

@php
    $user    = auth()->user();
    $isStaff = $user->isAdmin() || $user->isProjectManager() || $user->isTeamMember();

    $colorMap = [
        'cyan'   => ['bg' => '#f0f9ff', 'color' => '#0369a1', 'border' => '#bae6fd'],
        'blue'   => ['bg' => '#eff6ff', 'color' => '#1d4ed8', 'border' => '#bfdbfe'],
        'green'  => ['bg' => '#f0fdf4', 'color' => '#15803d', 'border' => '#bbf7d0'],
        'amber'  => ['bg' => '#fffbeb', 'color' => '#b45309', 'border' => '#fde68a'],
        'red'    => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca'],
        'purple' => ['bg' => '#f5f3ff', 'color' => '#7c3aed', 'border' => '#ddd6fe'],
        'muted'  => ['bg' => '#f8fafc', 'color' => '#64748b', 'border' => '#e2e8f0'],
    ];
@endphp

<div style="max-width:800px;margin:0 auto;">

    {{-- ── Page Header ──────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:var(--text);margin:0;">Notifications</h2>
            <p style="font-size:13px;color:var(--muted);margin-top:3px;">
                @if($isStaff)
                    System alerts — new orders, low stock, and user activity
                @else
                    Your order updates and account notifications
                @endif
            </p>
        </div>

        {{-- Action buttons --}}
        <div style="display:flex;gap:8px;align-items:center;">
            @if($notifications->isNotEmpty())
                {{-- Mark all read (AJAX) --}}
                <button id="btn-mark-all-read" onclick="markAllRead()"
                    class="btn btn-ghost" style="font-size:12px;padding:6px 12px;">
                    ✓ Mark all read
                </button>

                {{-- Clear all --}}
                <form method="POST" action="{{ route('notifications.clear-all') }}"
                      onsubmit="return confirm('Clear all notifications? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="font-size:12px;padding:6px 12px;">
                        🗑 Clear All
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ── Role context pill ───────────────────────────────────────── --}}
    <div style="margin-bottom:16px;">
        @if($isStaff)
            <span class="badge badge-blue">
                🛡 Admin/Staff View — Showing system-wide alerts
            </span>
        @else
            <span class="badge badge-cyan">
                👤 Customer View — Showing your order updates
            </span>
        @endif
        @if($unreadCount > 0)
            <span class="badge badge-red" style="margin-left:6px;">
                {{ $unreadCount }} unread (now marked as read)
            </span>
        @endif
    </div>

    {{-- ── Notification List ───────────────────────────────────────── --}}
    @if($notifications->isEmpty())
        <div class="card" style="padding:48px;text-align:center;">
            <div style="font-size:48px;margin-bottom:12px;">🔔</div>
            <p style="font-size:15px;font-weight:600;color:var(--text);margin:0 0 6px;">
                All caught up!
            </p>
            <p style="font-size:13px;color:var(--muted);">
                You have no notifications yet.
                @if($isStaff)
                    Notifications will appear here when new orders are placed or stock runs low.
                @else
                    Notifications will appear here when your order status is updated.
                @endif
            </p>
        </div>
    @else
        <div class="card" style="overflow:hidden;">
            @foreach($notifications as $notification)
                @php
                    $data   = $notification->data;
                    $color  = $colorMap[$data['color'] ?? 'muted'];
                    $isRead = $notification->read_at !== null;
                    $icon   = $data['icon'] ?? '📋';
                    $title  = $data['title'] ?? 'Notification';
                    $msg    = $data['message'] ?? '';
                    $url    = $data['action_url'] ?? null;
                    $label  = $data['action_label'] ?? 'View';
                @endphp

                <div id="notif-{{ $notification->id }}"
                     style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;
                            border-bottom:1px solid var(--border);
                            background:{{ $isRead ? 'transparent' : 'rgba(14,165,233,0.04)' }};
                            transition:background 0.2s;position:relative;"
                     @if(!$isRead) data-unread="true" @endif>

                    {{-- Unread indicator dot --}}
                    @if(!$isRead)
                        <span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);
                                     width:6px;height:6px;border-radius:50%;background:#0ea5e9;"></span>
                    @endif

                    {{-- Icon bubble --}}
                    <div style="width:38px;height:38px;border-radius:10px;flex-shrink:0;
                                background:{{ $color['bg'] }};border:1px solid {{ $color['border'] }};
                                display:flex;align-items:center;justify-content:center;font-size:18px;">
                        {{ $icon }}
                    </div>

                    {{-- Content --}}
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                            <p style="font-size:13px;font-weight:{{ $isRead ? '500' : '600' }};
                                      color:{{ $color['color'] }};margin:0;">{{ $title }}</p>
                            <span style="font-size:11px;color:var(--muted);">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p style="font-size:12px;color:var(--text2);margin:0 0 8px;line-height:1.5;">
                            {{ $msg }}
                        </p>
                        <div style="display:flex;align-items:center;gap:8px;">
                            @if($url)
                                <a href="{{ $url }}" class="btn btn-ghost"
                                   style="font-size:11px;padding:4px 10px;">
                                    {{ $label }} →
                                </a>
                            @endif
                            {{-- Dismiss button --}}
                            <form method="POST"
                                  action="{{ route('notifications.destroy', $notification->id) }}"
                                  onsubmit="dismissNotif(event, '{{ $notification->id }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="background:none;border:none;font-size:11px;
                                               color:var(--muted);cursor:pointer;padding:4px 6px;"
                                        onmouseover="this.style.color='var(--red)'"
                                        onmouseout="this.style.color='var(--muted)'">
                                    ✕ Dismiss
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div style="margin-top:16px;">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif

</div>

@push('scripts')
<script>
// Mark all read via AJAX
function markAllRead() {
    fetch('{{ route("notifications.mark-all-read") }}', {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(r => r.json())
    .then(() => {
        // Remove unread styling from all items
        document.querySelectorAll('[data-unread]').forEach(el => {
            el.style.background = 'transparent';
            el.removeAttribute('data-unread');
            const dot = el.querySelector('span[style*="background:#0ea5e9"]');
            if (dot) dot.remove();
        });
        // Reset sidebar badge
        const badge = document.getElementById('notif-badge-sidebar');
        const dot   = document.getElementById('notif-dot-sidebar');
        if (badge) badge.style.display = 'none';
        if (dot)   dot.style.display   = 'none';

        djlnToast('All notifications marked as read.');
    });
}

// Dismiss one notification via AJAX (prevents page reload)
function dismissNotif(e, id) {
    e.preventDefault();
    fetch('{{ url("/notifications") }}/' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(r => r.json())
    .then(() => {
        const el = document.getElementById('notif-' + id);
        if (el) {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.3s';
            setTimeout(() => el.remove(), 300);
        }
        djlnToast('Notification dismissed.');
    });
}
</script>
@endpush
@endsection
