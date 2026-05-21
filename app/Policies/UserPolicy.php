<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * ✅ SOVEREIGN'S DECREE: Only Admin can access user management
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the user.
     * ✅ SOVEREIGN'S DECREE: Only Admin can view user details
     */
    public function view(User $user, User $targetUser): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create users.
     * ✅ SOVEREIGN'S DECREE: Only Admin can create users
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the user.
     * ✅ SOVEREIGN'S DECREE: Only Admin can modify users
     */
    public function update(User $user, User $targetUser): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the user.
     * ✅ SOVEREIGN'S DECREE: Only Admin can delete users
     * Additional safety checks in controller prevent deletion of self or last admin
     */
    public function delete(User $user, User $targetUser): bool
    {
        return $user->isAdmin();
    }
}
