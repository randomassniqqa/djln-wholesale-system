@extends('layouts.app')
@section('title', 'Add User — DJLN Marketing')
@section('page-title', '➕ Add User')
@section('content')
<div style="max-width:480px;">
    <nav style="font-size:11px;color:var(--muted);margin-bottom:16px;display:flex;gap:8px;align-items:center;">
        <a href="{{ route('users.index') }}" style="color:var(--muted);text-decoration:none;" onmouseover="this.style.color='var(--cyan)'" onmouseout="this.style.color='var(--muted)'">Users</a>
        <span>/</span><span>New</span>
    </nav>

    <form method="POST" action="{{ route('users.store') }}" class="panel" style="padding:24px;">
        @csrf
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="form-label">Full Name <span style="color:var(--red);">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Maria Santos">
                @error('name')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Email Address <span style="color:var(--red);">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="email@djln.com">
                @error('email')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Role <span style="color:var(--red);">*</span></label>
                <select name="role" required class="form-input">
                    @foreach($roles as $val => $label)
                    <option value="{{ $val }}" {{ old('role') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Contact Number</label>
                <input type="tel" name="contact_number" value="{{ old('contact_number') }}"
                       class="form-input mono" placeholder="09171234567"
                       maxlength="20">
                <p style="font-size:10px;color:var(--muted);margin-top:4px;">PH format: 09XX-XXX-XXXX or +63XXXXXXXXXX</p>
                @error('contact_number')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Password <span style="color:var(--red);">*</span></label>
                <input type="password" name="password" required class="form-input" placeholder="Min. 8 characters">
                @error('password')<p style="font-size:11px;color:var(--red);margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Confirm Password <span style="color:var(--red);">*</span></label>
                <input type="password" name="password_confirmation" required class="form-input">
            </div>
            <p style="font-size:11px;color:var(--muted);padding:10px;background:rgba(14,165,233,0.05);border:1px solid rgba(14,165,233,0.15);border-radius:6px;">
                ℹ️ Admin-created accounts are automatically verified — no email required.
            </p>
            <div style="display:flex;gap:10px;padding-top:8px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary">💾 Create User</button>
                <a href="{{ route('users.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

