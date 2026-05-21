<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function landing(): RedirectResponse
    {
        // Authenticated users → Inventory Dashboard
        // Guests → Login page (no public landing for B2B system)
        return auth()->check()
            ? redirect()->route('inventory.dashboard')
            : redirect()->route('login');
    }

    public function preferences(): View
    {
        return view('profile.preferences');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_preference' => ['required', 'in:light,dark,system'],
        ]);

        $request->user()->update([
            'theme_preference' => $validated['theme_preference'],
        ]);

        return redirect()->route('profile.preferences')->with('success', 'Theme updated successfully');
    }
}