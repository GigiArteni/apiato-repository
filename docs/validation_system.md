# Validation - Data Validation & Business Rules

Complete guide to Apiato Repository's validation system for automatic data validation, business rule enforcement, and error handling with seamless integration.

## 📚 Table of Contents

- [Understanding Repository Validation](#-understanding-repository-validation)
- [Basic Validation Setup](#-basic-validation-setup)
- [Advanced Validation Rules](#-advanced-validation-rules)
- [Custom Validators](#-custom-validators)
- [Business Rule Validation](#-business-rule-validation)
- [Error Handling](#-error-handling)
- [Conditional Validation](#-conditional-validation)
- [Performance Optimization](#-performance-optimization)

## 🛡️ Understanding Repository Validation

Repository validation provides automatic data validation before database operations, ensuring data integrity and business rule compliance with zero configuration required.

### Validation Flow

```php
// Automatic validation flow:
$user = $repository->create($data);
// 1. Validation rules checked automatically
// 2. Business rules enforced
// 3. Data sanitized and prepared
// 4. Database operation performed
// 5. Post-validation hooks executed

// If validation fails:
// 1. ValidationException thrown
// 2. Database operation cancelled
// 3. Error details provided
// 4. No partial data saved
```

### Benefits

```php
/**
 * Automatic validation provides:
 * 
 * ✅ Data integrity enforcement
 * ✅ Business rule compliance
 * ✅ Consistent error handling
 * ✅ Automatic sanitization
 * ✅ Performance optimization
 * ✅ Security protection
 * ✅ Documentation through rules
 */
```

## 🔧 Basic Validation Setup

### Simple Repository Validation

```php
class UserRepository extends BaseRepository
{
    /**
     * Validation rules for create operations
     */
    protected $rules = [
        'create' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
        ],
        
        'update' => [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,{id}',
            'password' => 'sometimes|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
        ],
    ];

    public function model()
    {
        return User::class;
    }

    /**
     * Create user with automatic validation
     */
    public function createUser(array $data)
    {
        // Validation happens automatically using 'create' rules
        return $this->create($data);
    }

    /**
     * Update user with automatic validation
     */
    public function updateUser($id, array $data)
    {
        // Validation happens automatically using 'update' rules
        // {id} placeholder is automatically replaced
        return $this->update($data, $id);
    }
}
```

### Custom Validator Class

```php
<?php

namespace App\Validators;

use Apiato\Repository\Contracts\ValidatorInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Advanced validator with custom logic
 */
class UserValidator implements ValidatorInterface
{
    const RULE_CREATE = 'create';
    const RULE_UPDATE = 'update';

    protected $rules = [
        self::RULE_CREATE => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
        ],
        
        self::RULE_UPDATE => [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,{id}',
            'password' => 'sometimes|string|min:8|confirmed',
            'role_id' => 'sometimes|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
        ],
    ];

    protected $messages = [
        'email.unique' => 'This email address is already registered.',
        'password.min' => 'Password must be at least 8 characters long.',
        'role_id.exists' => 'The selected role is invalid.',
    ];

    protected $data = [];
    protected $errors = [];

    public function with(array $input)
    {
        $this->data = $input;
        return $this;
    }

    public function passesCreate()
    {
        return $this->passes(self::RULE_CREATE);
    }

    public function passesUpdate()
    {
        return $this->passes(self::RULE_UPDATE);
    }

    public function passes($action = null)
    {
        $rules = $this->rules[$action] ?? [];
        
        // Apply custom business logic
        $rules = $this->applyBusinessRules($rules, $action);
        
        $validator = Validator::make($this->data, $rules, $this->messages);
        
        if ($validator->fails()) {
            $this->errors = $validator->errors()->toArray();
            return false;
        }
        
        return true;
    }

    public function errors()
    {
        return $this->errors;
    }

    /**
     * Apply custom business rules
     */
    protected function applyBusinessRules(array $rules, $action): array
    {
        // Example: Different password requirements for admins
        if (isset($this->data['role_id']) && $this->isAdminRole($this->data['role_id'])) {
            $rules['password'] = str_replace('min:8', 'min:12', $rules['password']);
        }
        
        // Example: Require department for certain roles
        if (isset($this->data['role_id']) && $this->requiresDepartment($this->data['role_id'])) {
            $rules['department_id'] = 'required|exists:departments,id';
        }
        
        return $rules;
    }

    protected function isAdminRole($roleId): bool
    {
        // Check if role is admin (could be cached)
        return app('App\Repositories\RoleRepository')
            ->find($roleId)
            ->name === 'admin';
    }

    protected function requiresDepartment($roleId): bool
    {
        $rolesRequiringDepartment = ['manager', 'supervisor', 'team_lead'];
        
        $role = app('App\Repositories\RoleRepository')->find($roleId);
        
        return in_array($role->name, $rolesRequiringDepartment);
    }
}

// Repository using custom validator
class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }

    public function validator()
    {
        return UserValidator::class;
    }
}
```

## 📋 Advanced Validation Rules

### Context-Aware Validation

```php
class PostRepository extends BaseRepository
{
    protected $rules = [
        'create' => [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:100',
            'status' => 'required|in:draft,published',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array|max:10',
            'tags.*' => 'exists:tags,id',
        ],
        
        'update' => [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string|min:100',
            'status' => 'sometimes|in:draft,published,archived',
            'category_id' => 'sometimes|exists:categories,id',
            'tags' => 'array|max:10',
            'tags.*' => 'exists:tags,id',
        ],
    ];

    public function model()
    {
        return Post::class;
    }

    /**
     * Validation with user context
     */
    public function createPost(array $data, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        
        // Add user-specific validation
        $this->addUserContextValidation($userId);
        
        // Add user_id to data
        $data['user_id'] = $userId;
        
        return $this->create($data);
    }

    /**
     * Publish post with additional validation
     */
    public function publishPost($id, array $data = [])
    {
        $post = $this->find($id);
        
        // Additional validation for publishing
        $this->validateForPublishing($post, $data);
        
        $data['status'] = 'published';
        $data['published_at'] = now();
        
        return $this->update($data, $id);
    }

    protected function addUserContextValidation($userId)
    {
        $user = app('App\Repositories\UserRepository')->find($userId);
        
        // Different rules based on user role
        if ($user->hasRole('author')) {
            // Authors can only create drafts
            $this->rules['create']['status'] = 'required|in:draft';
        } elseif ($user->hasRole('editor')) {
            // Editors can publish immediately
            $this->rules['create']['status'] = 'required|in:draft,published';
        }
        
        // Check user's category permissions
        $allowedCategories = $user->allowedCategories->pluck('id')->toArray();
        if (!empty($allowedCategories)) {
            $categoryRule = 'required|in:' . implode(',', $allowedCategories);
            $this->rules['create']['category_id'] = $categoryRule;
            $this->rules['update']['category_id'] = str_replace('required', 'sometimes', $categoryRule);
        }
    }

    protected function validateForPublishing($post, array $data)
    {
        $validator = Validator::make(array_merge($post->toArray(), $data), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:500', // Longer content for published posts
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'required|string', // Require featured image for publishing
            'meta_description' => 'required|string|max:160',
        ], [
            'content.min' => 'Published posts must have at least 500 characters.',
            'featured_image.required' => 'A featured image is required for published posts.',
            'meta_description.required' => 'A meta description is required for SEO.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
```

### Dynamic Validation Rules

```php
class DynamicValidationRepository extends BaseRepository
{
    /**
     * Build validation rules dynamically
     */
    protected function getValidationRules($action, array $data = []): array
    {
        $baseRules = $this->rules[$action] ?? [];
        
        // Add conditional rules based on data
        if (isset($data['type'])) {
            $baseRules = array_merge($baseRules, $this->getTypeSpecificRules($data['type']));
        }
        
        // Add date-based rules
        $baseRules = array_merge($baseRules, $this->getDateBasedRules());
        
        // Add permission-based rules
        $baseRules = array_merge($baseRules, $this->getPermissionBasedRules());
        
        return $baseRules;
    }
    
    protected function getTypeSpecificRules($type): array
    {
        return match($type) {
            'premium' => [
                'subscription_id' => 'required|exists:subscriptions,id',
                'features' => 'required|array|min:1',
            ],
            'basic' => [
                'subscription_id' => 'nullable',
                'features' => 'nullable|array',
            ],
            'trial' => [
                'trial_ends_at' => 'required|date|after:today',
                'subscription_id' => 'nullable',
            ],
            default => [],
        };
    }
    
    protected function getDateBasedRules(): array
    {
        $rules = [];
        
        // Different rules based on current date
        if (now()->isWeekend()) {
            $rules['priority'] = 'required|in:normal,low'; // No high priority on weekends
        }
        
        // Holiday restrictions
        if ($this->isHoliday()) {
            $rules['approval_required'] = 'required|boolean|accepted';
        }
        
        return $rules;
    }
    
    protected function getPermissionBasedRules(): array
    {
        $user = auth()->user();
        $rules = [];
        
        if (!$user || !$user->hasPermission('create_premium_content')) {
            $rules['premium'] = 'prohibited';
        }
        
        if (!$user || !$user->hasPermission('set_priority')) {
            $rules['priority'] = 'in:normal,low';
        }
        
        return $rules;
    }
}
```

### Multi-Step Validation

```php
class MultiStepValidationRepository extends BaseRepository
{
    /**
     * Validate data in multiple steps
     */
    public function createWithMultiStepValidation(array $data)
    {
        // Step 1: Basic validation
        $this->validateStep1($data);
        
        // Step 2: Business logic validation
        $this->validateStep2($data);
        
        // Step 3: External service validation
        $this->validateStep3($data);
        
        // Step 4: Final validation and creation
        return $this->create($data);
    }
    
    protected function validateStep1(array $data)
    {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator, 'Basic validation failed');
        }
    }
    
    protected function validateStep2(array $data)
    {
        // Check business rules
        if ($this->isEmailBlacklisted($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['This email domain is not allowed.']
            ]);
        }
        
        if ($this->hasReachedUserLimit()) {
            throw ValidationException::withMessages([
                'general' => ['User registration limit reached.']
            ]);
        }
    }
    
    protected function validateStep3(array $data)
    {
        // Validate with external services
        if (!$this->validateEmailWithExternalService($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['Email validation failed with external service.']
            ]);
        }
        
        if (!$this->validatePasswordStrength($data['password'])) {
            throw ValidationException::withMessages([
                'password' => ['Password does not meet security requirements.']
            ]);
        }
    }
}
```

## 🏗️ Custom Validators

### Complex Business Rule Validator

```php
<?php

namespace App\Validators;

use Apiato\Repository\Contracts\ValidatorInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderValidator implements ValidatorInterface
{
    const RULE_CREATE = 'create';
    const RULE_UPDATE = 'update';
    
    protected $data = [];
    protected $errors = [];
    
    public function with(array $input)
    {
        $this->data = $input;
        return $this;
    }
    
    public function passesCreate()
    {
        return $this->passes(self::RULE_CREATE);
    }
    
    public function passesUpdate()
    {
        return $this->passes(self::RULE_UPDATE);
    }
    
    public function passes($action = null)
    {
        $rules = $this->getRules($action);
        
        $validator = Validator::make($this->data, $rules, $this->getMessages());
        
        // Add custom validation logic
        $validator->after(function ($validator) use ($action) {
            $this->validateBusinessRules($validator, $action);
        });
        
        if ($validator->fails()) {
            $this->errors = $validator->errors()->toArray();
            return false;
        }
        
        return true;
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    protected function getRules($action): array
    {
        $baseRules = [
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'shipping_address' => 'required|array',
            'payment_method' => 'required|in:credit_card,paypal,bank_transfer',
        ];
        
        if ($action === self::RULE_UPDATE) {
            // Make fields optional for updates
            $baseRules = array_map(function($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $baseRules);
        }
        
        return $baseRules;
    }
    
    protected function getMessages(): array
    {
        return [
            'items.required' => 'An order must contain at least one item.',
            'items.*.product_id.exists' => 'One or more products are invalid.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }
    
    protected function validateBusinessRules($validator, $action)
    {
        // Validate inventory availability
        $this->validateInventory($validator);
        
        // Validate customer credit limit
        $this->validateCreditLimit($validator);
        
        // Validate order total
        $this->validateOrderTotal($validator);
        
        // Validate shipping rules
        $this->validateShippingRules($validator);
        
        // Validate business hours (for urgent orders)
        $this->validateBusinessHours($validator);
    }
    
    protected function validateInventory($validator)
    {
        if (!isset($this->data['items'])) {
            return;
        }
        
        foreach ($this->data['items'] as $index => $item) {
            $product = \App\Models\Product::find($item['product_id']);
            
            if ($product && $product->stock < $item['quantity']) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    "Insufficient stock. Only {$product->stock} available."
                );
            }
        }
    }
    
    protected function validateCreditLimit($validator)
    {
        if (!isset($this->data['customer_id'])) {
            return;
        }
        
        $customer = \App\Models\Customer::find($this->data['customer_id']);
        $orderTotal = $this->calculateOrderTotal();
        
        if ($customer && ($customer->outstanding_balance + $orderTotal) > $customer->credit_limit) {
            $validator->errors()->add(
                'customer_id',
                'Order exceeds customer credit limit.'
            );
        }
    }
    
    protected function validateOrderTotal($validator)
    {
        $total = $this->calculateOrderTotal();
        
        if ($total < 10) {
            $validator->errors()->add(
                'items',
                'Order total must be at least $10.00.'
            );
        }
        
        if ($total > 50000) {
            $validator->errors()->add(
                'items',
                'Order total exceeds maximum allowed amount. Please contact sales.'
            );
        }
    }
    
    protected function validateShippingRules($validator)
    {
        if (!isset($this->data['shipping_address'])) {
            return;
        }
        
        $address = $this->data['shipping_address'];
        
        // Check if we ship to this location
        if (!$this->canShipToLocation($address)) {
            $validator->errors()->add(
                'shipping_address',
                'We do not currently ship to this location.'
            );
        }
        
        // Check for hazardous materials restrictions
        if ($this->containsHazardousMaterials() && !$this->canShipHazardousToLocation($address)) {
            $validator->errors()->add(
                'items',
                'Hazardous materials cannot be shipped to this location.'
            );
        }
    }
    
    protected function validateBusinessHours($validator)
    {
        if (isset($this->data['urgent']) && $this->data['urgent']) {
            if (!$this->isBusinessHours()) {
                $validator->errors()->add(
                    'urgent',
                    'Urgent orders can only be placed during business hours (9 AM - 5 PM).'
                );
            }
        }
    }
    
    protected function calculateOrderTotal(): float
    {
        if (!isset($this->data['items'])) {
            return 0;
        }
        
        return collect($this->data['items'])
            ->sum(function ($item) {
                return $item['quantity'] * $item['price'];
            });
    }
    
    protected function canShipToLocation(array $address): bool
    {
        // Implementation for shipping location validation
        return true; // Simplified
    }
    
    protected function containsHazardousMaterials(): bool
    {
        // Check if any items are hazardous
        return false; // Simplified
    }
    
    protected function canShipHazardousToLocation(array $address): bool
    {
        // Check hazardous material shipping rules
        return false; // Simplified
    }
    
    protected function isBusinessHours(): bool
    {
        $now = now();
        return $now->isWeekday() && $now->hour >= 9 && $now->hour < 17;
    }
}
```

### Async Validation Validator

```php
<?php

namespace App\Validators;

use Apiato\Repository\Contracts\ValidatorInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AsyncValidationValidator implements ValidatorInterface
{
    const RULE_CREATE = 'create';
    const RULE_UPDATE = 'update';
    
    protected $data = [];
    protected $errors = [];
    
    public function with(array $input)
    {
        $this->data = $input;
        return $this;
    }
    
    public function passesCreate()
    {
        return $this->passes(self::RULE_CREATE);
    }
    
    public function passesUpdate()
    {
        return $this->passes(self::RULE_UPDATE);
    }
    
    public function passes($action = null)
    {
        // Basic validation first
        if (!$this->passesBasicValidation($action)) {
            return false;
        }
        
        // Async validations
        if (!$this->passesAsyncValidations()) {
            return false;
        }
        
        return true;
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    protected function passesBasicValidation($action): bool
    {
        $rules = [
            'email' => 'required|email',
            'company_name' => 'required|string|max:255',
            'tax_id' => 'required|string',
            'website' => 'nullable|url',
        ];
        
        $validator = \Validator::make($this->data, $rules);
        
        if ($validator->fails()) {
            $this->errors = $validator->errors()->toArray();
            return false;
        }
        
        return true;
    }
    
    protected function passesAsyncValidations(): bool
    {
        $validations = [
            'email' => $this->validateEmailAsync(),
            'tax_id' => $this->validateTaxIdAsync(),
            'company' => $this->validateCompanyAsync(),
        ];
        
        $failures = [];
        
        foreach ($validations as $field => $result) {
            if (!$result['valid']) {
                $failures[$field] = $result['message'];
            }
        }
        
        if (!empty($failures)) {
            $this->errors = array_merge($this->errors, $failures);
            return false;
        }
        
        return true;
    }
    
    protected function validateEmailAsync(): array
    {
        $email = $this->data['email'];
        $cacheKey = "email_validation_{$email}";
        
        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Validate with external email validation service
            $response = Http::timeout(5)->post('https://api.emailvalidation.com/validate', [
                'email' => $email,
                'api_key' => config('services.email_validation.key'),
            ]);
            
            $result = [
                'valid' => $response->json('valid', true),
                'message' => $response->json('message', 'Email validation failed'),
            ];
            
            // Cache result for 1 hour
            Cache::put($cacheKey, $result, 3600);
            
            return $result;
            
        } catch (\Exception $e) {
            // Fail gracefully - don't block user registration for external service issues
            return ['valid' => true, 'message' => ''];
        }
    }
    
    protected function validateTaxIdAsync(): array
    {
        $taxId = $this->data['tax_id'];
        $cacheKey = "tax_id_validation_{$taxId}";
        
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Validate with tax authority API
            $response = Http::timeout(10)->get('https://api.taxauthority.com/validate', [
                'tax_id' => $taxId,
                'api_key' => config('services.tax_authority.key'),
            ]);
            
            $result = [
                'valid' => $response->json('status') === 'valid',
                'message' => $response->json('status') !== 'valid' 
                    ? 'Invalid tax ID number' 
                    : '',
            ];
            
            // Cache result for 24 hours (tax IDs change rarely)
            Cache::put($cacheKey, $result, 86400);
            
            return $result;
            
        } catch (\Exception $e) {
            // For tax validation, we might want to be stricter
            Log::warning('Tax ID validation service unavailable', [
                'tax_id' => $taxId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'valid' => false,
                'message' => 'Unable to validate tax ID at this time. Please try again later.',
            ];
        }
    }
    
    protected function validateCompanyAsync(): array
    {
        $companyName = $this->data['company_name'];
        $website = $this->data['website'] ?? null;
        
        try {
            // Check company against business directory
            $response = Http::timeout(5)->get('https://api.businessdirectory.com/search', [
                'company_name' => $companyName,
                'website' => $website,
                'api_key' => config('services.business_directory.key'),
            ]);
            
            $found = $response->json('found', false);
            
            return [
                'valid' => true, // We don't block registration if company not found
                'message' => '',
                'company_verified' => $found,
            ];
            
        } catch (\Exception $e) {
            return ['valid' => true, 'message' => ''];
        }
    }
}
```

## 🏢 Business Rule Validation

### Rule Engine Integration

```php
class BusinessRuleValidator implements ValidatorInterface
{
    protected $ruleEngine;
    protected $data = [];
    protected $errors = [];
    
    public function __construct(RuleEngine $ruleEngine)
    {
        $this->ruleEngine = $ruleEngine;
    }
    
    public function with(array $input)
    {
        $this->data = $input;
        return $this;
    }
    
    public function passes($action = null)
    {
        // Load business rules for this action
        $rules = $this->ruleEngine->getRulesForAction($action, $this->data);
        
        foreach ($rules as $rule) {
            $result = $rule->evaluate($this->data);
            
            if (!$result->passes()) {
                $this->errors[] = $result->getMessage();
            }
        }
        
        return empty($this->errors);
    }
    
    public function errors()
    {
        return $this->errors;
    }
}

// Business rule classes
class CustomerAgeRule extends BusinessRule
{
    public function evaluate($data): RuleResult
    {
        if (isset($data['birth_date'])) {
            $age = Carbon::parse($data['birth_date'])->age;
            
            if ($age < 18) {
                return RuleResult::fail('Customer must be at least 18 years old');
            }
        }
        
        return RuleResult::pass();
    }
}

class CreditLimitRule extends BusinessRule
{
    public function evaluate($data): RuleResult
    {
        if (isset($data['customer_id']) && isset($data['order_total'])) {
            $customer = Customer::find($data['customer_id']);
            
            if ($customer->outstanding_balance + $data['order_total'] > $customer->credit_limit) {
                return RuleResult::fail('Order would exceed customer credit limit');
            }
        }
        
        return RuleResult::pass();
    }
}
```

### Workflow-Based Validation

```php
class WorkflowValidator implements ValidatorInterface
{
    protected $workflow;
    protected $data = [];
    protected $errors = [];
    
    public function with(array $input)
    {
        $this->data = $input;
        return $this;
    }
    
    public function passes($action = null)
    {
        // Create workflow context
        $context = new ValidationContext($this->data, $action);
        
        // Define validation workflow
        $this->workflow = WorkflowBuilder::create()
            ->step('basic_validation', function($context) {
                return $this->validateBasicRules($context);
            })
            ->step('business_rules', function($context) {
                return $this->validateBusinessRules($context);
            })
            ->step('external_validation', function($context) {
                return $this->validateExternalSources($context);
            })
            ->step('final_checks', function($context) {
                return $this->validateFinalChecks($context);
            })
            ->onFailure(function($step, $error, $context) {
                $this->errors[] = "Validation failed at step '{$step}': {$error}";
            });
        
        return $this->workflow->execute($context);
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    protected function validateBasicRules($context)
    {
        // Basic Laravel validation
        $validator = Validator::make($context->getData(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);
        
        return $validator->passes();
    }
    
    protected function validateBusinessRules($context)
    {
        // Custom business logic
        $data = $context->getData();
        
        // Example: Check if user can register based on company policy
        if ($this->isRegistrationRestricted($data)) {
            return false;
        }
        
        return true;
    }
    
    protected function validateExternalSources($context)
    {
        // External API validations
        return $this->validateWithExternalServices($context->getData());
    }
    
    protected function validateFinalChecks($context)
    {
        // Final validation before database operation
        return $this->performFinalSecurityChecks($context->getData());
    }
}
```

## 🚨 Error Handling

### Comprehensive Error Response

```php
class ValidatedRepository extends BaseRepository
{
    /**
     * Enhanced error handling for validation failures
     */
    public function create(array $attributes)
    {
        try {
            return parent::create($attributes);
        } catch (ValidationException $e) {
            $this->handleValidationError($e, 'create', $attributes);
            throw $e;
        }
    }
    
    public function update(array $attributes, $id)
    {
        try {
            return parent::update($attributes, $id);
        } catch (ValidationException $e) {
            $this->handleValidationError($e, 'update', $attributes, $id);
            throw $e;
        }
    }
    
    protected function handleValidationError(ValidationException $e, $action, $data, $id = null)
    {
        // Log validation error with context
        Log::warning('Repository validation failed', [
            'repository' => static::class,
            'action' => $action,
            'errors' => $e->errors(),
            'data' => $this->sanitizeDataForLogging($data),
            'id' => $id,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
        
        // Track validation failures for analytics
        $this->trackValidationFailure($action, $e->errors());
        
        // Send notification for critical failures
        if ($this->isCriticalValidationFailure($e->errors())) {
            $this->notifyAdministrators($e, $action, $data);
        }
    }
    
    protected function sanitizeDataForLogging(array $data): array
    {
        // Remove sensitive fields from logging
        $sensitiveFields = ['password', 'password_confirmation', 'credit_card', 'ssn'];
        
        return collect($data)->except($sensitiveFields)->toArray();
    }
    
    protected function trackValidationFailure($action, $errors)
    {
        // Track with analytics service
        Analytics::track('validation_failure', [
            'repository' => class_basename(static::class),
            'action' => $action,
            'error_count' => count($errors),
            'error_fields' => array_keys($errors),
        ]);
    }
    
    protected function isCriticalValidationFailure($errors): bool
    {
        $criticalFields = ['email', 'payment_method', 'security_code'];
        
        return collect($errors)
            ->keys()
            ->intersect($criticalFields)
            ->isNotEmpty();
    }
    
    protected function notifyAdministrators($exception, $action, $data)
    {
        // Send notification to administrators
        Notification::route('slack', config('logging.channels.slack.url'))
            ->notify(new ValidationFailureNotification($exception, $action, $data));
    }
}
```

### User-Friendly Error Messages

```php
class UserFriendlyErrorRepository extends BaseRepository
{
    protected $userFriendlyMessages = [
        'email.unique' => 'This email address is already registered. Try logging in instead.',
        'password.min' => 'Your password should be at least 8 characters long for security.',
        'credit_card.required' => 'Please provide a valid payment method to continue.',
        'phone.regex' => 'Please enter a valid phone number (e.g., +1-555-123-4567).',
    ];
    
    /**
     * Transform technical errors to user-friendly messages
     */
    protected function transformValidationErrors(ValidationException $e): array
    {
        $errors = $e->errors();
        $transformed = [];
        
        foreach ($errors as $field => $messages) {
            $transformed[$field] = array_map(function($message) use ($field) {
                $key = "{$field}." . $this->extractRuleName($message);
                
                return $this->userFriendlyMessages[$key] ?? $message;
            }, $messages);
        }
        
        return $transformed;
    }
    
    protected function extractRuleName($message): string
    {
        // Extract rule name from Laravel validation message
        if (str_contains($message, 'required')) return 'required';
        if (str_contains($message, 'unique')) return 'unique';
        if (str_contains($message, 'min')) return 'min';
        if (str_contains($message, 'max')) return 'max';
        if (str_contains($message, 'email')) return 'email';
        
        return 'generic';
    }
    
    /**
     * API endpoint with user-friendly errors
     */
    public function apiCreate(array $data)
    {
        try {
            $model = $this->create($data);
            
            return response()->json([
                'success' => true,
                'data' => $model,
                'message' => 'Created successfully!',
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $this->transformValidationErrors($e),
                'message' => 'Please check the information you provided and try again.',
            ], 422);
        }
    }
}
```

## ⚡ Performance Optimization

### Cached Validation

```php
class CachedValidationRepository extends BaseRepository
{
    /**
     * Cache validation rules for performance
     */
    protected function getValidationRules($action): array
    {
        $cacheKey = "validation_rules_{$action}_" . static::class;
        
        return Cache::remember($cacheKey, 3600, function() use ($action) {
            return $this->buildValidationRules($action);
        });
    }
    
    /**
     * Cache external validation results
     */
    protected function validateWithExternalService($field, $value): bool
    {
        $cacheKey = "external_validation_{$field}_{$value}";
        
        return Cache::remember($cacheKey, 1800, function() use ($field, $value) {
            return $this->performExternalValidation($field, $value);
        });
    }
    
    /**
     * Batch validation for better performance
     */
    public function validateBatch(array $records): array
    {
        $results = [];
        $toValidate = [];
        
        // Check cache for each record
        foreach ($records as $index => $record) {
            $cacheKey = "validation_" . md5(serialize($record));
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                $results[$index] = $cached;
            } else {
                $toValidate[$index] = $record;
            }
        }
        
        // Validate uncached records
        foreach ($toValidate as $index => $record) {
            $result = $this->validateSingle($record);
            $results[$index] = $result;
            
            // Cache result
            $cacheKey = "validation_" . md5(serialize($record));
            Cache::put($cacheKey, $result, 300); // 5 minutes
        }
        
        return $results;
    }
}
```

### Lazy Validation

```php
class LazyValidationRepository extends BaseRepository
{
    /**
     * Only validate when necessary
     */
    protected function shouldValidate($action, $data): bool
    {
        // Skip validation for trusted sources
        if ($this->isTrustedSource()) {
            return false;
        }
        
        // Skip validation for system operations
        if ($this->isSystemOperation()) {
            return false;
        }
        
        // Only validate changed fields for updates
        if ($action === 'update') {
            return $this->hasSignificantChanges($data);
        }
        
        return true;
    }
    
    protected function isTrustedSource(): bool
    {
        return request()->hasHeader('X-Internal-Request') &&
               hash_equals(
                   request()->header('X-Internal-Request'),
                   config('app.internal_request_key')
               );
    }
    
    protected function isSystemOperation(): bool
    {
        return app()->runningInConsole() ||
               auth()->check() && auth()->user()->hasRole('system');
    }
    
    protected function hasSignificantChanges($data): bool
    {
        $significantFields = ['email', 'password', 'role_id', 'status'];
        
        return collect($data)
            ->keys()
            ->intersect($significantFields)
            ->isNotEmpty();
    }
}
```

---

**Next:** Learn about **[Generators](generators.md)** for automatic code generation and scaffolding.