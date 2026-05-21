<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * DJLN Marketing — User Model
 *
 * Email verification is intentionally DISABLED for this system.
 * MustVerifyEmail is NOT implemented — all users can log in immediately.
 * New users created by admins automatically receive email_verified_at = now()
 * via UserController::store().
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'password',
        'role',
        'theme_preference',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return strtolower(trim((string) $this->role)) === 'admin';
    }

    /**
     * Check if user is project manager
     */
    public function isProjectManager(): bool
    {
        return strtolower(trim((string) $this->role)) === 'project_manager';
    }

    /**
     * Check if user is team member
     */
    public function isTeamMember(): bool
    {
        return strtolower(trim((string) $this->role)) === 'team_member';
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return strtolower(trim((string) $this->role)) === 'client';
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role): bool
    {
        return strtolower(trim((string) $this->role)) === strtolower(trim((string) $role));
    }

    // ✅ RELATIONSHIPS FOR ROLE-BASED ACCESS

    /**
     * Orders placed by this user (for Customers)
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    /**
     * Orders processed by this user (for Staff)
     */
    public function processedOrders()
    {
        return $this->hasMany(Order::class, 'processed_by', 'id');
    }
}
