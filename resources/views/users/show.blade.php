@extends('layouts.app')
@section('title', $user->name . ' — Users')
@section('page-title', '👤 ' . $user->name)
@section('content')
<div style="max-width:560px;display:flex;flex-direction:column;gap:16px;">

    <nav style="font-size:11px;color:var(--muted);display:flex;gap:8px;align-items:center;">
        <a href="{{ route('users.index') }}" style="color:var(--muted);text-decoration:none;" onmouseover="this.style.color='var(--cyan)'" onmouseout="this.style.color='var(--muted)'">Users</a>
        <span>/</span><span>{{ $user->name }}</span>
    </nav>

    <div class="panel" style="padding:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--purple));display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:white;flex-shrink:0;">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <p style="font-size:16px;font-weight:600;color:var(--text);">{{ $user->name }}</p>
                <p style="font-size:13px;color:var(--muted);margin-bottom:2px;">{{ $user->email }}</p>
                <p class="mono" style="font-size:12px;color:var(--muted);opacity:{{ $user->contact_number ? '1' : '0.5' }};">
                    {{ $user->contact_number ? '📞 '.$user->contact_number : '— no phone number' }}
                </p>
                <div style="display:flex;gap:8px;margin-top:6px;">
                    @php
                        $roleColor = match($user->role) {
                            'admin' => 'badge-purple', 'project_manager' => 'badge-cyan', 'client' => 'badge-green', default => 'badge-muted'
                        };
                        $roleLabel = match($user->role) {
                            'admin' => 'Admin', 'project_manager' => 'Manager', 'team_member' => 'Staff', 'client' => 'Customer', default => ucfirst($user->role)
                        };
                    @endphp
                    <span class="badge {{ $roleColor }}">{{ $roleLabel }}</span>
                </div>
            </div>
        </div>
        @if(auth()->id() !== $user->id)
        <div style="display:flex;gap:8px;">
            <a href="{{ route('users.edit', $user) }}" class="btn" style="padding:6px 14px;background:rgba(251,191,36,0.1);color:var(--yellow);border-color:rgba(251,191,36,0.3);">✏️ Edit</a>
            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete {{ addslashes($user->name) }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger" style="padding:6px 14px;">🗑️ Delete</button>
            </form>
        </div>
        @endif
    </div>

    <div class="panel" style="padding:20px;">
        <p style="font-size:10px;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted);margin-bottom:12px;">Account Details</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <p style="font-size:11px;color:var(--muted);">Joined</p>
                <p style="font-size:13px;color:var(--text);font-family:'JetBrains Mono',monospace;">{{ $user->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:var(--muted);">Last Updated</p>
                <p style="font-size:13px;color:var(--text);font-family:'JetBrains Mono',monospace;">{{ $user->updated_at->diffForHumans() }}</p>
            </div>

            <div>
                <p style="font-size:11px;color:var(--muted);">User ID</p>
                <p style="font-size:13px;color:var(--text);font-family:'JetBrains Mono',monospace;">#{{ $user->id }}</p>
            </div>
        </div>
    </div>

    <a href="{{ route('users.index') }}" class="btn btn-ghost" style="align-self:flex-start;">← Back to Users</a>
</div>
@endsection

