<?php

declare(strict_types=1);

namespace Apiato\Repository\Validators;

use Apiato\Repository\Contracts\ValidatorInterface;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;

/**
 * Base validator for repository validation
 */
abstract class BaseValidator implements ValidatorInterface
{
    protected ValidatorFactory $validator;
    protected array $rules = [];
    protected array $messages = [];
    protected array $attributes = [];

    public function __construct(ValidatorFactory $validator)
    {
        $this->validator = $validator;
    }

    public function validate(array $data, string $action = 'create'): array
    {
        $rules = $this->getRules($action);
        
        if (empty($rules)) {
            return $data;
        }

        $validator = $this->validator->make(
            $data,
            $rules,
            $this->getMessages(),
            $this->getAttributes()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function getRules(string $action = 'create'): array
    {
        $rules = $this->rules;

        if (isset($this->rules[$action])) {
            $rules = $this->rules[$action];
        }

        return $this->processRules($rules, $action);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    protected function processRules(array $rules, string $action): array
    {
        $processedRules = [];

        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $processedRules[$field] = $this->processRule($rule, $action);
            } elseif (is_array($rule)) {
                $processedRules[$field] = array_map(function ($r) use ($action) {
                    return $this->processRule($r, $action);
                }, $rule);
            }
        }

        return $processedRules;
    }

    protected function processRule(string $rule, string $action): string
    {
        // Remove required on update if field is not present
        if ($action === 'update' && str_contains($rule, 'required')) {
            $rule = str_replace('required', 'sometimes|required', $rule);
        }

        return $rule;
    }
}
