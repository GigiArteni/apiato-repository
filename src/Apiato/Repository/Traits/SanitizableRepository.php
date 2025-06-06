<?php

namespace Apiato\Repository\Traits;

use Illuminate\Support\Facades\Log;
use Apiato\Repository\Events\DataSanitizedEvent;

/**
 * Sanitizable Repository Trait
 * 
 * Integrates with Apiato's sanitizeInput() method and provides fallback
 * sanitization for enhanced security in database operations.
 * 
 * Features:
 * - Automatic integration with Apiato's request sanitization
 * - Fallback sanitization for non-Apiato projects  
 * - Field-specific sanitization rules
 * - HashId-aware sanitization
 * - Audit trail for sanitization changes
 */
trait SanitizableRepository
{
    protected bool $skipSanitization = false;
    protected array $customSanitizationRules = [];

    /**
     * Skip sanitization for the next operation
     * Useful for trusted data sources or when manual sanitization is done
     * 
     * @param bool $status
     * @return $this
     * 
     * @example
     * $repository->skipSanitization()->create($trustedData);
     */
    public function skipSanitization($status = true)
    {
        $this->skipSanitization = $status;
        return $this;
    }

    /**
     * Set custom sanitization rules for specific fields
     * 
     * @param array $rules
     * @return $this
     * 
     * @example
     * $repository->setSanitizationRules([
     *     'email' => 'email',
     *     'name' => 'string|strip_tags',
     *     'bio' => 'html_purify'
     * ])->create($data);
     */
    public function setSanitizationRules(array $rules)
    {
        $this->customSanitizationRules = $rules;
        return $this;
    }

    /**
     * Main sanitization method - integrates with Apiato's sanitizeInput()
     * 
     * @param array $data Raw input data
     * @param string $operation Operation type (create, update, etc.)
     * @return array Sanitized data
     */
    protected function sanitizeData(array $data, string $operation = 'create'): array
    {
        // Skip if sanitization is disabled globally or for this operation
        if (!$this->shouldSanitize($operation)) {
            return $data;
        }

        $originalData = $data;

        try {
            // Step 1: Use Apiato's sanitizeInput() if available
            $data = $this->apiatoSanitization($data);

            // Step 2: Apply custom field-specific sanitization
            $data = $this->applyFieldSanitization($data);

            // Step 3: Sanitize HashId fields (ensure they're valid)
            $data = $this->sanitizeHashIdFields($data);

            // Step 4: Apply fallback sanitization for non-Apiato projects
            $data = $this->fallbackSanitization($data);

            // Step 5: Audit sanitization changes if enabled
            $this->auditSanitization($originalData, $data, $operation);

        } catch (\Exception $e) {
            Log::warning('Repository sanitization failed', [
                'repository' => get_class($this),
                'operation' => $operation,
                'error' => $e->getMessage(),
                'data_keys' => array_keys($originalData)
            ]);

            // Return original data if sanitization fails (fail-safe approach)
            return $originalData;
        }

        // Reset skip flag for next operation
        $this->skipSanitization = false;

        return $data;
    }

    /**
     * Check if sanitization should be applied for the given operation
     */
    protected function shouldSanitize(string $operation): bool
    {
        if ($this->skipSanitization) {
            return false;
        }

        if (!config('repository.security.sanitize_input', true)) {
            return false;
        }

        return config("repository.security.sanitize_on.{$operation}", true);
    }

    /**
     * Use Apiato's sanitizeInput() method if available
     * This is the primary sanitization method for Apiato projects
     */
    protected function apiatoSanitization(array $data): array
    {
        $request = request();

        // Check if we're in an Apiato environment with sanitizeInput method
        if ($request && method_exists($request, 'sanitizeInput')) {
            try {
                // Apiato's sanitizeInput expects the request data format
                $sanitized = $request->sanitizeInput($data);
                
                if (config('repository.security.audit_sanitization')) {
                    Log::info('Apiato sanitization applied', [
                        'repository' => get_class($this),
                        'fields_count' => count($data),
                        'changed_fields' => array_keys(array_diff_assoc($data, $sanitized))
                    ]);
                }

                return $sanitized;
            } catch (\Exception $e) {
                Log::warning('Apiato sanitization failed, using fallback', [
                    'error' => $e->getMessage(),
                    'repository' => get_class($this)
                ]);
            }
        }

        return $data;
    }

    /**
     * Apply field-specific sanitization rules
     */
    protected function applyFieldSanitization(array $data): array
    {
        $rules = array_merge(
            $this->getDefaultSanitizationRules(),
            $this->customSanitizationRules
        );

        foreach ($data as $field => $value) {
            if (isset($rules[$field]) && $value !== null) {
                $data[$field] = $this->sanitizeField($value, $rules[$field], $field);
            }
        }

        return $data;
    }

    /**
     * Get default sanitization rules based on configuration
     */
    protected function getDefaultSanitizationRules(): array
    {
        $rules = [];

        // Email fields
        $emailFields = config('repository.security.sanitize_fields.email_fields', []);
        foreach ($emailFields as $field) {
            $rules[$field] = 'email';
        }

        // HTML fields (require HTML purification)
        $htmlFields = config('repository.security.sanitize_fields.html_fields', []);
        foreach ($htmlFields as $field) {
            $rules[$field] = 'html_purify';
        }

        return $rules;
    }

    /**
     * Sanitize individual field based on rule
     */
    protected function sanitizeField($value, string $rule, string $fieldName)
    {
        if (!is_string($value) && !is_array($value)) {
            return $value;
        }

        switch ($rule) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);

            case 'string':
            case 'strip_tags':
                return is_string($value) ? strip_tags($value) : $value;

            case 'html_purify':
                return $this->purifyHtml($value);

            case 'numeric':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);

            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);

            case 'alphanum':
                return preg_replace('/[^a-zA-Z0-9]/', '', $value);

            default:
                return $value;
        }
    }

    /**
     * Sanitize HashId fields to ensure they're valid
     * Prevents injection of malicious data in ID fields
     */
    protected function sanitizeHashIdFields(array $data): array
    {
        foreach ($data as $field => $value) {
            if ($this->isIdField($field) && is_string($value)) {
                // Validate HashId format (alphanumeric, specific length)
                if (!preg_match('/^[a-zA-Z0-9]{5,}$/', $value) && !is_numeric($value)) {
                    Log::warning('Invalid HashId format detected', [
                        'field' => $field,
                        'value' => $value,
                        'repository' => get_class($this)
                    ]);
                    
                    // Remove invalid HashId to prevent injection
                    unset($data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * Fallback sanitization for non-Apiato projects
     */
    protected function fallbackSanitization(array $data): array
    {
        if (!config('repository.security.fallback_sanitization', true)) {
            return $data;
        }

        $excludeFields = config('repository.security.sanitize_fields.exclude', []);

        foreach ($data as $field => $value) {
            // Skip excluded fields (passwords, tokens, etc.)
            if (in_array($field, $excludeFields)) {
                continue;
            }

            // Basic string sanitization
            if (is_string($value)) {
                // Remove potential XSS
                $data[$field] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $data;
    }

    /**
     * HTML purification (requires HTML Purifier or similar)
     */
    protected function purifyHtml($value): string
    {
        if (!is_string($value)) {
            return $value;
        }

        // Basic HTML sanitization - replace with HTML Purifier if available
        if (class_exists('\HTMLPurifier')) {
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($value);
        }

        // Fallback: strip dangerous tags
        $allowedTags = '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
        return strip_tags($value, $allowedTags);
    }

    /**
     * Audit sanitization changes for security monitoring
     */
    protected function auditSanitization(array $original, array $sanitized, string $operation): void
    {
        if (!config('repository.security.audit_sanitization', false)) {
            return;
        }

        $changes = [];
        foreach ($original as $field => $value) {
            if (isset($sanitized[$field]) && $sanitized[$field] !== $value) {
                $changes[$field] = [
                    'original' => $this->truncateForLog($value),
                    'sanitized' => $this->truncateForLog($sanitized[$field])
                ];
            }
        }

        if (!empty($changes)) {
            // Fire event for audit systems
            event(new DataSanitizedEvent($this, $original, $sanitized, $operation, $changes));

            // Log for security monitoring
            Log::info('Data sanitization applied', [
                'repository' => get_class($this),
                'operation' => $operation,
                'fields_changed' => array_keys($changes),
                'changes_count' => count($changes),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }

    /**
     * Truncate sensitive data for logging
     */
    protected function truncateForLog($value, int $maxLength = 100): string
    {
        if (!is_string($value)) {
            return gettype($value);
        }

        return strlen($value) > $maxLength ? substr($value, 0, $maxLength) . '...' : $value;
    }

    /**
     * Batch sanitize multiple records (for bulk operations)
     * 
     * @param array $records Array of data arrays
     * @param string $operation
     * @return array Sanitized records
     * 
     * @example
     * $sanitizedRecords = $repository->batchSanitize([
     *     ['name' => 'John', 'email' => 'john@test.com'],
     *     ['name' => 'Jane', 'email' => 'jane@test.com']
     * ], 'bulk_create');
     */
    public function batchSanitize(array $records, string $operation = 'bulk_operations'): array
    {
        if (!config('repository.security.sanitize_on.bulk_operations', true)) {
            return $records;
        }

        $chunkSize = config('repository.bulk_operations.chunk_size', 1000);
        $sanitized = [];

        // Process in chunks to avoid memory issues
        foreach (array_chunk($records, $chunkSize) as $chunk) {
            foreach ($chunk as $record) {
                $sanitized[] = $this->sanitizeData($record, $operation);
            }
        }

        return $sanitized;
    }
}