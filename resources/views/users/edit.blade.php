@extends('layouts.app')
@section('title', 'Edit — ' . $user->name)
@section('page-title', '✏️ Edit: ' . $user->name)
@section('content')
<div style="max-width:480px;">
    <nav style="font-size:11px;color:var(--muted);margin-bottom:16px;display:flex;gap:8px;align-items:center;">
        <a href="{{ route('users.index') }}" style="color:var(--muted);text-decoration:none;" onmouseover="this.style.color='var(--cyan)'" onmouseout="this.style.color='var(--muted)'">Users</a>
        <span>/</span>
        <a href="{{ route('users.show', $user) }}" style="color:var(--muted);text-decoration:none;" onmouseover="this.style.color='var(--cyan)'" onmouseout="this.style.color='var(--muted)'">{{ $user->name }}</a>
        <span>/</span><span>Edit</span>
    </nav>

    <form method="POST" action="{{ route('users.update', $user) }}" class="panel" style="padding:24px;">
        @csrf @method('PUT')
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="form-label">Full Name <span style="color:var(--red);">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                @error('name')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Email Address <span style="color:var(--red);">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
                @error('email')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Role <span style="color:var(--red);">*</span></label>
                <select name="role" required class="form-input">
                    @foreach($roles as $val => $label)
                    <option value="{{ $val }}" {{ old('role', $user->role) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Contact Number</label>
                <input type="tel" name="contact_number"
                       value="{{ old('contact_number', $user->contact_number) }}"
                       class="form-input mono" placeholder="09171234567"
                       maxlength="20">
                <p style="font-size:10px;color:var(--muted);margin-top:4px;">PH format: 09XX-XXX-XXXX or +63XXXXXXXXXX</p>
                @error('contact_number')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div style="padding:12px;background:var(--obsidian);border:1px solid var(--border);border-radius:6px;">
                <p style="font-size:11px;color:var(--muted);margin-bottom:10px;">Leave password blank to keep existing.</p>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div>
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Min. 8 characters">
                        @error('password')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-input">
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;padding-top:8px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="{{ route('users.show', $user) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

