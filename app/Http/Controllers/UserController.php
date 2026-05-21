<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * DJLN Marketing — UserController
 * Manages system users (admin-only).
 * All legacy Task/Project relationship references removed.
 */
class UserController extends Controller
{
    // ── Roles available in DJLN system ─────────────────────────────────────
    private array $roles = [
        'admin'           => 'Admin — Full system access',
        'project_manager' => 'Manager — Can create & edit inventory',
        'team_member'     => 'Staff — View-only access',
        'client'          => 'Customer — Shop & place orders',
    ];

    // ── Index ───────────────────────────────────────────────────────────────
    public function index(): View|RedirectResponse
    {
        try {
            Gate::authorize('viewAny', User::class);

            $users = User::query()
                ->orderByRaw("CASE LOWER(role)
                    WHEN 'admin'           THEN 0
                    WHEN 'project_manager' THEN 1
                    WHEN 'team_member'     THEN 2
                    ELSE 3 END")
                ->orderBy('name')
                ->paginate(25);

            return view('users.index', compact('users'));

        } catch (Exception $e) {
            Log::error('UserController@index: ' . $e->getMessage());
            return redirect()->route('inventory.dashboard')
                ->with('error', 'Unauthorized: Admin access required.');
        }
    }

    // ── Create ──────────────────────────────────────────────────────────────
    public function create(): View|RedirectResponse
    {
        try {
            Gate::authorize('create', User::class);
            return view('users.create', ['roles' => $this->roles]);
        } catch (Exception $e) {
            Log::error('UserController@create: ' . $e->getMessage());
            return redirect()->route('inventory.dashboard')
                ->with('error', 'Unauthorized.');
        }
    }

    // ── Store ───────────────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        try {
            Gate::authorize('create', User::class);

            $validated = $request->validate([
                'name'           => ['required', 'string', 'max:100'],
                'email'          => ['required', 'string', 'max:100', 'unique:users,email'],
                'contact_number' => ['nullable', 'string', 'regex:/^(\+?63|0)\d{9,10}$/', 'max:20'],
                'role'           => ['required', 'in:' . implode(',', array_keys($this->roles))],
                'password'       => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            // Mark as verified immediately (admin-created accounts skip email flow)
            $validated['password']          = Hash::make($validated['password']);
            $validated['email_verified_at'] = now();

            User::create($validated);

            return redirect()->route('users.index')
                ->with('success', "User \"{$validated['name']}\" created successfully.");

        } catch (Exception $e) {
            Log::error('UserController@store: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    // ── Show ────────────────────────────────────────────────────────────────
    public function show(User $user): View|RedirectResponse
    {
        try {
            Gate::authorize('view', $user);
            return view('users.show', compact('user'));
        } catch (Exception $e) {
            Log::error('UserController@show: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'Could not load user.');
        }
    }

    // ── Edit ────────────────────────────────────────────────────────────────
    public function edit(User $user): View|RedirectResponse
    {
        try {
            Gate::authorize('update', $user);
            return view('users.edit', ['user' => $user, 'roles' => $this->roles]);
        } catch (Exception $e) {
            Log::error('UserController@edit: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'Unauthorized.');
        }
    }

    // ── Update ──────────────────────────────────────────────────────────────
    public function update(Request $request, User $user): RedirectResponse
    {
        try {
            Gate::authorize('update', $user);

            $validated = $request->validate([
                'name'           => ['required', 'string', 'max:100'],
                'email'          => ['required', 'string', 'max:100', 'unique:users,email,' . $user->id],
                'contact_number' => ['nullable', 'string', 'regex:/^(\+?63|0)\d{9,10}$/', 'max:20'],
                'role'           => ['required', 'in:' . implode(',', array_keys($this->roles))],
                'password'       => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            return redirect()->route('users.show', $user)
                ->with('success', 'User updated successfully.');

        } catch (Exception $e) {
            Log::error('UserController@update: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update user.');
        }
    }

    // ── Destroy ─────────────────────────────────────────────────────────────
    public function destroy(User $user): RedirectResponse
    {
        try {
            Gate::authorize('delete', $user);

            if (auth()->id() === $user->id) {
                return back()->with('error', 'You cannot delete your own account.');
            }

            if ($user->isAdmin() && User::whereRaw('LOWER(role) = ?', ['admin'])->count() === 1) {
                return back()->with('error', 'Cannot delete the last admin account.');
            }

            $user->delete();

            return redirect()->route('users.index')
                ->with('success', "User \"{$user->name}\" deleted.");

        } catch (Exception $e) {
            Log::error('UserController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete user.');
        }
    }

    // ── Audit Logs ──────────────────────────────────────────────────────────
    public function auditLogs(Request $request): View|RedirectResponse
    {
        try {
            if (!auth()->user()->isAdmin()) {
                abort(403);
            }

            // AuditLog model may not exist — handle gracefully
            if (!class_exists(\App\Models\AuditLog::class)) {
                return redirect()->route('users.index')
                    ->with('error', 'Audit log feature is not available.');
            }

            $query = \App\Models\AuditLog::with('user');

            if ($request->filled('action'))     $query->where('action', $request->action);
            if ($request->filled('model_type')) $query->where('model_type', $request->model_type);
            if ($request->filled('from_date'))  $query->whereDate('created_at', '>=', $request->from_date);

            $logs = $query->orderByDesc('created_at')->paginate(50);

            return view('users.audit-logs', compact('logs'));

        } catch (Exception $e) {
            Log::error('UserController@auditLogs: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'Failed to load audit logs.');
        }
    }
}
