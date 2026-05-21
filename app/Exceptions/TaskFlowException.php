<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * TASKFLOW CUSTOM EXCEPTION
 * 
 * Purpose: Provide structured exception handling for all TaskFlow application errors
 * With automatic logging and user-friendly error messages
 * 
 * Usage: throw new TaskFlowException('User error message', 'log_context_data')
 * 
 * Before: Generic exceptions with no context
 * After: Structured exceptions with automatic logging and categorization
 */
class TaskFlowException extends Exception
{
    /**
     * User-friendly error message (displayed to users)
     */
    protected $userMessage;

    /**
     * Error context for logging
     */
    protected $context = [];

    /**
     * Error category for filtering logs
     */
    protected $category;

    /**
     * Constructor with structured error handling
     */
    public function __construct(
        string $userMessage,
        string $internalMessage = '',
        array $context = [],
        string $category = 'general',
        int $code = 0
    ) {
        $this->userMessage = $userMessage;
        $this->context = $context;
        $this->category = $category;

        // If internal message not provided, use user message
        $internalMessage = $internalMessage ?: $userMessage;

        parent::__construct($internalMessage, $code);

        // Automatically log exception with context
        $this->logException();
    }

    /**
     * Log exception with structured context
     */
    private function logException(): void
    {
        Log::error('TaskFlow Exception - ' . $this->category, [
            'user_message' => $this->userMessage,
            'internal_message' => $this->message,
            'category' => $this->category,
            'code' => $this->code,
            'context' => $this->context,
            'file' => $this->file,
            'line' => $this->line,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Get error category
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Get context data
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
