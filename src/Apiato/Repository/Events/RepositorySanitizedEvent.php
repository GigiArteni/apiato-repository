<?php

namespace Apiato\Repository\Events;

use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Data Sanitized Event
 * 
 * Fired when repository data is sanitized for security purposes.
 * Useful for audit trails, security monitoring, and compliance.
 */
class RepositorySanitizedEvent extends RepositoryEventBase
{
    protected string $action = "data_sanitized";
    protected array $originalData;
    protected array $sanitizedData;
    protected string $operation;
    protected array $changes;

    /**
     * Create a new event instance
     */
    public function __construct(
        RepositoryInterface $repository, 
        array $originalData, 
        array $sanitizedData, 
        string $operation = 'unknown',
        array $changes = []
    ) {
        $this->repository = $repository;
        $this->originalData = $originalData;
        $this->sanitizedData = $sanitizedData;
        $this->operation = $operation;
        $this->changes = $changes;
        $this->model = null; // No single model for sanitization events
    }

    /**
     * Get the original data before sanitization
     */
    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    /**
     * Get the sanitized data after processing
     */
    public function getSanitizedData(): array
    {
        return $this->sanitizedData;
    }

    /**
     * Get the operation that triggered sanitization
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Get the specific changes made during sanitization
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * Get the fields that were changed
     */
    public function getChangedFields(): array
    {
        return array_keys($this->changes);
    }

    /**
     * Get count of fields that were sanitized
     */
    public function getChangedFieldsCount(): int
    {
        return count($this->changes);
    }

    /**
     * Check if specific field was sanitized
     */
    public function wasFieldSanitized(string $field): bool
    {
        return array_key_exists($field, $this->changes);
    }

    /**
     * Get event data as array for logging/auditing
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'operation' => $this->operation,
            'fields_changed' => $this->getChangedFields(),
            'changes_count' => $this->getChangedFieldsCount(),
            'has_sensitive_data' => $this->hasSensitiveData(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Check if sanitization involved sensitive data
     */
    protected function hasSensitiveData(): bool
    {
        $sensitiveFields = ['password', 'email', 'phone', 'ssn', 'credit_card'];
        
        foreach ($this->getChangedFields() as $field) {
            foreach ($sensitiveFields as $sensitive) {
                if (str_contains(strtolower($field), $sensitive)) {
                    return true;
                }
            }
        }

        return false;
    }
}