<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Audit log belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable action label
     */
    public function getActionLabel(): string
    {
        return match($this->action) {
            'create' => '🆕 Created',
            'update' => '✏️ Updated',
            'delete' => '🗑️ Deleted',
            'assign' => '👤 Assigned',
            'complete' => '✅ Completed',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get summary of changes for display
     */
    public function getChangeSummary(): string
    {
        if (!$this->changes) {
            return 'N/A';
        }

        $changes = [];
        foreach ($this->changes as $field => $change) {
            $before = $change['before'] ?? 'null';
            $after = $change['after'] ?? 'null';
            $changes[] = "$field: $before → $after";
        }

        return implode(', ', array_slice($changes, 0, 2));
    }
}
