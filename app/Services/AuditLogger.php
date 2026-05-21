<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Log a model action to audit trail
     */
    public static function log(
        string $action,
        string $modelType,
        int $modelId,
        ?array $changes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'changes' => $changes,
                'ip_address' => $ipAddress ?? request()->ip(),
                'user_agent' => $userAgent ?? request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Audit log creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Calculate changes between old and new values
     */
    public static function calculateChanges(array $old, array $new): array
    {
        $changes = [];
        $fields = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($fields as $field) {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'before' => $oldValue,
                    'after' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
