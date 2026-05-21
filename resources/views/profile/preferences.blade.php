@extends('layouts.app')

@section('page-title', 'Theme Preferences')

@section('content')
<div style="max-width:800px;margin:0 auto;padding-top:20px;">
    
    <div class="card" style="padding:32px;">
        <h1 style="font-size:24px;font-weight:700;color:var(--text);margin-bottom:24px;">Preferences</h1>

        <!-- Theme Selection -->
        <div style="border-top:1px solid var(--border);padding-top:24px;">
            <h2 style="font-size:16px;font-weight:600;color:var(--text);margin-bottom:16px;">🎨 Appearance</h2>
            
            <form method="POST" action="{{ route('profile.update-theme') }}" style="display:flex;flex-direction:column;gap:16px;">
                @csrf
                @method('PATCH')

                <fieldset style="display:flex;flex-direction:column;gap:12px;">
                    <legend style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:8px;">Theme Preference</legend>
                    
                    @php $pref = auth()->user()->theme_preference ?? 'system'; @endphp

                    <label style="display:flex;align-items:center;padding:16px;border:2px solid {{ $pref === 'light' ? 'var(--cyan)' : 'var(--border)' }};border-radius:8px;cursor:pointer;background:{{ $pref === 'light' ? 'rgba(14,165,233,0.05)' : 'var(--surface)' }};transition:all 0.2s;">
                        <input type="radio" name="theme_preference" value="light" {{ $pref === 'light' ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--cyan);">
                        <span style="margin-left:12px;">
                            <span style="display:block;font-size:14px;font-weight:600;color:var(--text);">Light Mode</span>
                            <span style="font-size:12px;color:var(--muted);">Bright background with dark text</span>
                        </span>
                    </label>

                    <label style="display:flex;align-items:center;padding:16px;border:2px solid {{ $pref === 'dark' ? 'var(--cyan)' : 'var(--border)' }};border-radius:8px;cursor:pointer;background:{{ $pref === 'dark' ? 'rgba(14,165,233,0.05)' : 'var(--surface)' }};transition:all 0.2s;">
                        <input type="radio" name="theme_preference" value="dark" {{ $pref === 'dark' ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--cyan);">
                        <span style="margin-left:12px;">
                            <span style="display:block;font-size:14px;font-weight:600;color:var(--text);">Dark Mode</span>
                            <span style="font-size:12px;color:var(--muted);">Dark background with light text, reduces eye strain</span>
                        </span>
                    </label>

                    <label style="display:flex;align-items:center;padding:16px;border:2px solid {{ $pref === 'system' ? 'var(--cyan)' : 'var(--border)' }};border-radius:8px;cursor:pointer;background:{{ $pref === 'system' ? 'rgba(14,165,233,0.05)' : 'var(--surface)' }};transition:all 0.2s;">
                        <input type="radio" name="theme_preference" value="system" {{ $pref === 'system' ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--cyan);">
                        <span style="margin-left:12px;">
                            <span style="display:block;font-size:14px;font-weight:600;color:var(--text);">System Default</span>
                            <span style="font-size:12px;color:var(--muted);">Use your device's settings</span>
                        </span>
                    </label>
                </fieldset>

                <div style="padding-top:16px;">
                    <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 20px;">
                        Save Preferences
                    </button>
                </div>
            </form>
        </div>

        <!-- Accessibility Info -->
        <div style="border-top:1px solid var(--border);margin-top:32px;padding-top:24px;">
            <h2 style="font-size:16px;font-weight:600;color:var(--text);margin-bottom:16px;">♿ Accessibility</h2>
            <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2);border-radius:8px;padding:16px;">
                <p style="font-size:13px;color:var(--blue);line-height:1.5;">
                    ✅ <strong style="font-weight:700;">WCAG 2.1 AA Compliance:</strong> All status indicators use icons + text, colors alone do not convey information. 
                    High contrast ratios maintained across all themes for colorblind accessibility.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
