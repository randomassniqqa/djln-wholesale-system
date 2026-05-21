<?php

namespace App\Http\Requests;

trait TaskValidationRules
{
    /**
     * Get common task validation rules
     * ✅ Shared between Store and Update requests to eliminate duplication
     * ✅ FIXED: Accept datetime-local format (Y-m-d\TH:i) from HTML5 input type
     */
    protected function getCommonTaskRules(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:pending,in_progress,completed',
            'priority' => 'required|in:low,medium,high,critical',
            'assigned_user_id' => 'nullable|integer|exists:users,id',
            // Accept flexible date input (datetime-local or plain date strings)
            'due_date' => 'nullable|date',
        ];
    }

    /**
     * Get common error messages
     * ✅ Shared between Store and Update requests
     */
    protected function getCommonMessages(): array
    {
        return [
            'project_id.required' => 'Project is required',
            'project_id.exists' => 'Selected project does not exist',
            'project_id.integer' => 'Project ID must be a valid number',
            'title.required' => 'Task title is required',
            'title.max' => 'Task title cannot exceed 255 characters',
            'priority.in' => 'Priority must be low, medium, high, or critical',
            'status.in' => 'Status must be pending, in_progress, or completed',
            'assigned_user_id.exists' => 'Selected user does not exist in the system',
            'assigned_user_id.integer' => 'User ID must be a valid number',
            'due_date.date' => 'Due date must be a valid date and time (use the date picker)',
        ];
    }

    /**
     * Normalize incoming due_date into a DB-safe datetime string.
     * Supports common UI/browser formats from different locales.
     */
    protected function normalizeDueDateValue(?string $due): ?string
    {
        if ($due === null || trim($due) === '') {
            return $due;
        }

        $due = trim($due);

        $formats = [
            'Y-m-d\\TH:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i',
            'd-m-Y H:i',
            'd/m/Y',
            'd-m-Y',
        ];

        foreach ($formats as $format) {
            try {
                $dt = \Carbon\Carbon::createFromFormat($format, $due);
                if (in_array($format, ['Y-m-d', 'd/m/Y', 'd-m-Y'], true)) {
                    $dt = $dt->startOfDay();
                }
                return $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Try next format.
            }
        }

        // Final fallback for parseable strings.
        try {
            return \Carbon\Carbon::parse($due)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $due;
        }
    }
}
