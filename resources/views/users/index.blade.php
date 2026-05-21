@extends('layouts.app')

@section('title', 'User Management — DJLN Marketing')
@section('page-title', '👥 User Management')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <p style="font-size:13px;color:var(--muted);">
            {{ $users->total() }} system user{{ $users->total() !== 1 ? 's' : '' }}
        </p>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </a>
    </div>

    <div class="card" style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                @php
                    $roleColor = match($u->role) {
                        'admin'           => 'badge-purple',
                        'project_manager' => 'badge-cyan',
                        'team_member'     => 'badge-muted',
                        'client'          => 'badge-green',
                        default           => 'badge-muted',
                    };
                    $roleLabel = match($u->role) {
                        'admin'           => '⚙️ Admin',
                        'project_manager' => '📋 Manager',
                        'team_member'     => '👤 Staff',
                        'client'          => '🛍️ Customer',
                        default           => ucfirst($u->role),
                    };
                @endphp
                <tr>
                    {{-- Name --}}
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--purple));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:white;flex-shrink:0;">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div>
                                <p style="font-weight:600;color:var(--text);font-size:13px;">{{ $u->name }}</p>
                                @if($u->id === auth()->id())
                                    <span class="badge badge-green" style="font-size:9px;">You</span>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td style="color:var(--muted);font-size:12px;">{{ $u->email }}</td>

                    {{-- Phone --}}
                    <td class="mono" style="font-size:12px;color:var(--muted);white-space:nowrap;">
                        {{ $u->contact_number ?? '—' }}
                    </td>

                    {{-- Role --}}
                    <td><span class="badge {{ $roleColor }}">{{ $roleLabel }}</span></td>



                    {{-- Joined --}}
                    <td class="mono" style="color:var(--muted);font-size:11px;">
                        {{ $u->created_at->format('M d, Y') }}
                    </td>

                    {{-- Actions --}}
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;align-items:center;">
                            <a href="{{ route('users.show', $u) }}"
                               class="btn btn-ghost" style="padding:4px 10px;font-size:11px;">View</a>
                            <a href="{{ route('users.edit', $u) }}"
                               class="btn" style="padding:4px 10px;font-size:11px;background:rgba(251,191,36,0.1);color:var(--amber);border-color:rgba(251,191,36,0.3);">Edit</a>
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $u) }}"
                                  onsubmit="return confirm('Permanently delete {{ addslashes($u->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="btn btn-danger" style="padding:4px 10px;font-size:11px;">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:48px;color:var(--muted);">
                        <p style="font-size:32px;">👥</p>
                        <p style="font-size:13px;margin-top:8px;">No users found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--border);">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
