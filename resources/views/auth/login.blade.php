<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — DJLN Marketing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0fdf4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-wrap {
            width: 100%;
            max-width: 440px;
        }

        /* ── Brand header ── */
        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(14,165,233,0.35);
        }
        .brand-name {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .brand-sub {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        /* ── Card ── */
        .login-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1px 4px rgba(0,0,0,0.04);
        }

        /* ── Alerts ── */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .alert-error p { font-size: 13px; color: #dc2626; font-weight: 500; }

        /* ── Form ── */
        .field-group { margin-bottom: 18px; }
        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 7px;
        }
        .field-wrap {
            position: relative;
        }
        .field-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.15s;
        }
        .field-input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid #e2e8f0;
            border-radius: 11px;
            font-size: 14px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field-input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
        }
        .field-input:focus + .field-icon,
        .field-wrap:focus-within .field-icon { color: #0ea5e9; }

        /* ── Submit ── */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(14,165,233,0.35);
            margin-top: 8px;
            letter-spacing: 0.01em;
        }
        .btn-submit:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14,165,233,0.4); }
        .btn-submit:active { transform: translateY(0); }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0 18px;
        }
        .divider-line { flex: 1; height: 1px; background: #e2e8f0; }
        .divider-text { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; white-space: nowrap; }

        /* ── Quick access buttons ── */
        .quick-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 22px; }
        .quick-btn {
            padding: 12px 8px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.15s;
            text-align: center;
        }
        .quick-btn:hover { border-color: #0ea5e9; background: #f0f9ff; transform: translateY(-1px); }
        .quick-btn-icon { font-size: 20px; display: block; margin-bottom: 4px; }
        .quick-btn-role { font-size: 12px; font-weight: 700; display: block; margin-bottom: 2px; }
        .quick-btn-user { font-size: 10px; color: #94a3b8; font-weight: 500; }
        .quick-btn-admin  { }
        .quick-btn-staff  { }
        .quick-btn-client { }

        /* ── Credentials hint ── */
        .creds-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .creds-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .creds-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
        }
        .creds-row:last-child { border-bottom: none; }
        .creds-label { color: #94a3b8; }
        .creds-val { font-weight: 700; color: #0f172a; font-family: monospace; background: #e2e8f0; padding: 2px 7px; border-radius: 5px; }

        /* ── Footer ── */
        .login-footer { text-align: center; margin-top: 24px; font-size: 11px; color: #94a3b8; }

        /* ── Role badge on quick btn ── */
        .role-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 3px; }
        .dot-admin  { background: #0ea5e9; }
        .dot-staff  { background: #6366f1; }
        .dot-client { background: #10b981; }
    </style>
</head>
<body>
<div class="login-wrap">

    {{-- Brand --}}
    <div class="brand-header">
        <div class="brand-logo">🛍️</div>
        <div class="brand-name">DJLN Marketing</div>
        <div class="brand-sub">Wholesale & Retail System</div>
    </div>

    {{-- Card --}}
    <div class="login-card">

        {{-- Errors --}}
        @if($errors->any())
            <div class="alert-error">
                <span style="font-size:18px;flex-shrink:0;">⚠️</span>
                <div>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf

            <div class="field-group">
                <label class="field-label" for="email">Username or Email</label>
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                    <input type="text" name="email" id="email"
                           value="{{ old('email') }}"
                           class="field-input"
                           placeholder="e.g. admin"
                           required autofocus autocomplete="username">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label" for="password">Password</label>
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input type="password" name="password" id="password"
                           class="field-input"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn-submit" id="login-btn">
                Sign In Securely →
            </button>
        </form>

        {{-- Quick access --}}
        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-text">Quick Demo Access</span>
            <div class="divider-line"></div>
        </div>

        <div class="quick-grid">
            {{-- Admin --}}
            <button type="button" class="quick-btn quick-btn-admin"
                    onclick="fillLogin('admin','password')"
                    title="Fill admin credentials">
                <span class="quick-btn-icon">👑</span>
                <span class="quick-btn-role" style="color:#0ea5e9;">
                    <span class="role-dot dot-admin"></span>Admin
                </span>
                <span class="quick-btn-user">admin</span>
            </button>

            {{-- Staff --}}
            <button type="button" class="quick-btn quick-btn-staff"
                    onclick="fillLogin('sarah','password')"
                    title="Fill staff credentials">
                <span class="quick-btn-icon">🧑‍💼</span>
                <span class="quick-btn-role" style="color:#6366f1;">
                    <span class="role-dot dot-staff"></span>Manager
                </span>
                <span class="quick-btn-user">sarah</span>
            </button>

            {{-- Customer --}}
            <button type="button" class="quick-btn quick-btn-client"
                    onclick="fillLogin('balloonbash','password')"
                    title="Fill customer credentials">
                <span class="quick-btn-icon">🛒</span>
                <span class="quick-btn-role" style="color:#10b981;">
                    <span class="role-dot dot-client"></span>Customer
                </span>
                <span class="quick-btn-user">Balloon Bash Co.</span>
            </button>
        </div>

        {{-- Credentials box --}}
        <div class="creds-box">
            <div class="creds-title">
                <span>🔑</span> Demo Credentials
            </div>
            <div class="creds-row">
                <span class="creds-label">👑 Admin</span>
                <span class="creds-val">admin</span>
            </div>
            <div class="creds-row">
                <span class="creds-label">🧑‍💼 Managers</span>
                <span class="creds-val">sarah · michael · emma</span>
            </div>
            <div class="creds-row">
                <span class="creds-label">🛒 Customers</span>
                <span class="creds-val">balloonbash · sweetoccasions</span>
            </div>
            <div class="creds-row" style="padding-top:10px;margin-top:6px;border-top:1px dashed #e2e8f0;border-bottom:none;">
                <span class="creds-label">Password (all)</span>
                <span class="creds-val">password</span>
            </div>
        </div>
    </div>

    <div class="login-footer">
        DJLN Marketing Wholesale System © {{ date('Y') }}
    </div>
</div>

<script>
function fillLogin(user, pass) {
    document.getElementById('email').value    = user;
    document.getElementById('password').value = pass;
    document.getElementById('email').focus();
}

// Prevent double-submit
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('login-btn');
    btn.disabled    = true;
    btn.textContent = 'Signing in…';
});
</script>
</body>
</html>
