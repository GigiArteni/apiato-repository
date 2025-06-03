# Criteria System Guide

Learn how to use the powerful criteria system for advanced query building and filtering.

## What are Criteria?

Criteria are classes that encapsulate query logic, making your repositories more flexible and maintainable. They implement the `CriteriaInterface` and can be applied to any repository.

## Creating Criteria

### Generate Criteria Class

```bash
php artisan make:criteria ActiveUsersCriteria
```

### Basic Criteria Implementation

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ActiveUsersCriteria implements CriteriaInterface
{
    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->where('status', 'active')
                    ->where('email_verified_at', '!=', null);
    }
}
```

## Built-in Criteria

### RequestCriteria

The `RequestCriteria` automatically applies filters based on HTTP request parameters:

```php
use Apiato\Repository\Criteria\RequestCriteria;

// In your controller
public function index(Request $request)
{
    return $this->userRepository
        ->pushCriteria(new RequestCriteria($request))
        ->paginate();
}
```

#### Request Parameters

**Search Parameters:**
```bash
# Basic search
GET /api/users?search=name:john

# Advanced search with operators
GET /api/users?search=name:like:john;email:gmail.com

# Multiple conditions with custom join
GET /api/users?search=name:john;status:active&searchJoin=and
```

**Filter Parameters:**
```bash
# Basic filters
GET /api/users?filter=status:active

# Multiple filters
GET /api/users?filter=status:active;role_id:1

# Date ranges
GET /api/users?filter=created_at:date_between:2024-01-01,2024-12-31

# Number ranges
GET /api/products?filter=price:between:100,500
```

**Include Parameters:**
```bash
# Basic includes
GET /api/users?include=profile,posts

# Count relationships
GET /api/users?include=posts_count,comments_count

# Nested includes
GET /api/users?include=profile.country,posts.comments
```

**Ordering:**
```bash
# Single field
GET /api/users?orderBy=created_at&sortedBy=desc

# Multiple fields
GET /api/users?orderBy=name,created_at&sortedBy=asc,desc
```

## Advanced Criteria Examples

### Date-based Criteria

```php
class RecentUsersCriteria implements CriteriaInterface
{
    public function __construct(
        private int $days = 30
    ) {}

    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->where('created_at', '>=', now()->subDays($this->days));
    }
}

// Usage
$recentUsers = $this->userRepository
    ->pushCriteria(new RecentUsersCriteria(7)) // Last 7 days
    ->all();
```

### Role-based Criteria

```php
class UsersByRoleCriteria implements CriteriaInterface
{
    public function __construct(
        private string $role
    ) {}

    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->whereHas('roles', function ($query) {
            $query->where('name', $this->role);
        });
    }
}

// Usage
$admins = $this->userRepository
    ->pushCriteria(new UsersByRoleCriteria('admin'))
    ->all();
```

### Geographic Criteria

```php
class UsersByLocationCriteria implements CriteriaInterface
{
    public function __construct(
        private string $country,
        private ?string $city = null
    ) {}

    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        $query = $model->whereHas('profile', function ($query) {
            $query->where('country', $this->country);
            
            if ($this->city) {
                $query->where('city', $this->city);
            }
        });

        return $query;
    }
}
```

### Search Criteria with HashId Support

```php
use Apiato\Repository\Traits\HashIdRepository;

class SearchUsersCriteria implements CriteriaInterface
{
    use HashIdRepository;

    public function __construct(
        private string $searchTerm,
        private array $fields = ['name', 'email']
    ) {
        $this->initializeHashIds();
    }

    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->where(function ($query) {
            foreach ($this->fields as $field) {
                $query->orWhere($field, 'like', "%{$this->searchTerm}%");
            }
            
            // If search term looks like HashId, also search by decoded ID
            if ($this->looksLikeHashId($this->searchTerm)) {
                $decodedId = $this->decodeHashId($this->searchTerm);
                if ($decodedId) {
                    $query->orWhere('id', $decodedId);
                }
            }
        });
    }
}
```

### Performance Criteria

```php
class OptimizedUsersCriteria implements CriteriaInterface
{
    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->select(['id', 'name', 'email', 'created_at'])
                    ->with(['profile:id,user_id,avatar'])
                    ->orderBy('id'); // Use indexed column for ordering
    }
}
```

## Using Multiple Criteria

### Chain Criteria

```php
$users = $this->userRepository
    ->pushCriteria(new ActiveUsersCriteria())
    ->pushCriteria(new RecentUsersCriteria(30))
    ->pushCriteria(new UsersByRoleCriteria('premium'))
    ->paginate();
```

### Conditional Criteria

```php
public function getUsers(Request $request)
{
    $repository = $this->userRepository
        ->pushCriteria(new RequestCriteria($request));

    // Add role filter if specified
    if ($request->has('role')) {
        $repository->pushCriteria(new UsersByRoleCriteria($request->role));
    }

    // Add location filter if specified
    if ($request->has('country')) {
        $repository->pushCriteria(new UsersByLocationCriteria(
            $request->country,
            $request->city
        ));
    }

    return $repository->paginate();
}
```

## Managing Criteria

### Skip Criteria

```php
// Skip all criteria
$users = $this->userRepository
    ->skipCriteria()
    ->all();

// Skip criteria temporarily
$users = $this->userRepository
    ->skipCriteria(true)
    ->all();

// Re-enable criteria
$this->userRepository->skipCriteria(false);
```

### Clear Criteria

```php
// Clear all criteria
$this->userRepository->clearCriteria();

// Add new criteria after clearing
$users = $this->userRepository
    ->clearCriteria()
    ->pushCriteria(new ActiveUsersCriteria())
    ->all();
```

### Remove Specific Criteria

```php
// Remove specific criteria type
$this->userRepository->popCriteria(new ActiveUsersCriteria());
```

### Get Applied Criteria

```php
$appliedCriteria = $this->userRepository->getCriteria();

foreach ($appliedCriteria as $criteria) {
    echo get_class($criteria) . "\n";
}
```

## Request Criteria Configuration

### Advanced Search Configuration

Configure search behavior in `config/repository.php`:

```php
'criteria' => [
    'params' => [
        'search' => 'search',
        'searchFields' => 'searchFields',
        'searchJoin' => 'searchJoin',        // AND/OR logic
        'filter' => 'filter',
        'filterJoin' => 'filterJoin',        // AND/OR logic
        'orderBy' => 'orderBy',
        'sortedBy' => 'sortedBy',
        'include' => 'include',
        'compare' => 'compare',              // Field comparisons
        'having' => 'having',                // Having conditions
        'groupBy' => 'groupBy',              // Group by fields
    ],
    
    'search' => [
        'default_join_operator' => 'OR',     // Default search logic
        'case_sensitive' => false,
        'date_format' => 'Y-m-d',
    ],
    
    'filters' => [
        'default_join_operator' => 'AND',    // Default filter logic
        'strict_typing' => true,
        'auto_cast_numbers' => true,
        'auto_parse_dates' => true,
    ],
],
```

### Field Comparison Criteria

```bash
# Compare fields within the same record
GET /api/events?compare=start_date:<=:end_date
GET /api/products?compare=sale_price:<=:original_price
GET /api/users?compare=last_login:>=:created_at
```

### Having Conditions

```bash
# Using HAVING clauses with aggregates
GET /api/users?having=posts_count:>:5
GET /api/categories?having=products_sum_price:>=:1000
```

### Group By Operations

```bash
# Group results by fields
GET /api/orders?groupBy=status,created_date
GET /api/users?groupBy=role_id&include=role
```

## Custom Request Criteria

Create your own request criteria for specific needs:

```php
class CustomRequestCriteria implements CriteriaInterface
{
    use HashIdRepository;

    public function __construct(
        private Request $request
    ) {
        $this->initializeHashIds();
    }

    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        // Custom search logic
        if ($search = $this->request->get('q')) {
            $model = $this->applyCustomSearch($model, $search);
        }

        // Custom filters
        if ($filters = $this->request->get('filters')) {
            $model = $this->applyCustomFilters($model, $filters);
        }

        // Custom includes
        if ($includes = $this->request->get('with')) {
            $model = $this->applyCustomIncludes($model, $includes);
        }

        return $model;
    }

    private function applyCustomSearch(Builder $model, string $search): Builder
    {
        return $model->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
        });
    }

    private function applyCustomFilters(Builder $model, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if ($this->isHashIdField($field) && $this->looksLikeHashId($value)) {
                $value = $this->decodeHashId($value);
            }
            
            $model = $model->where($field, $value);
        }

        return $model;
    }
}
```

## Testing Criteria

### Unit Tests

```php
use Tests\TestCase;
use App\Criteria\ActiveUsersCriteria;
use App\Models\User;

class ActiveUsersCriteriaTest extends TestCase
{
    public function test_applies_active_users_filter(): void
    {
        $criteria = new ActiveUsersCriteria();
        $model = User::query();
        $repository = $this->createMock(RepositoryInterface::class);

        $result = $criteria->apply($model, $repository);

        $this->assertStringContainsString('status', $result->toSql());
        $this->assertStringContainsString('active', $result->toSql());
    }
}
```

### Integration Tests

```php
public function test_criteria_with_repository(): void
{
    // Create test data
    User::factory()->create(['status' => 'active']);
    User::factory()->create(['status' => 'inactive']);

    // Apply criteria
    $activeUsers = $this->userRepository
        ->pushCriteria(new ActiveUsersCriteria())
        ->all();

    $this->assertCount(1, $activeUsers);
    $this->assertEquals('active', $activeUsers->first()->status);
}
```

## Best Practices

### 1. Single Responsibility

Each criteria should have a single, specific purpose:

```php
// Good: Specific purpose
class ActiveUsersCriteria
class RecentUsersCriteria
class PremiumUsersCriteria

// Bad: Multiple purposes
class ActiveRecentPremiumUsersCriteria
```

### 2. Parameterized Criteria

Make criteria flexible with parameters:

```php
class UsersByAgeCriteria implements CriteriaInterface
{
    public function __construct(
        private int $minAge,
        private ?int $maxAge = null
    ) {}
}
```

### 3. Repository-Agnostic

Write criteria that can work with any repository:

```php
class StatusCriteria implements CriteriaInterface
{
    public function __construct(
        private string $status,
        private string $field = 'status'
    ) {}

    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->where($this->field, $this->status);
    }
}
```

### 4. Performance Considerations

Always consider query performance:

```php
class OptimizedCriteria implements CriteriaInterface
{
    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model
            ->select(['id', 'name', 'email']) // Only select needed columns
            ->with(['profile:id,user_id,name']) // Optimize eager loading
            ->whereHas('posts', null, '>', 0) // Use exists instead of count
            ->orderBy('id'); // Use indexed column
    }
}
```

## Next Steps

- [Caching Strategy](caching.md) - Implement intelligent caching
- [HashId Integration](hashids.md) - Secure ID handling with criteria
- [Testing Guide](testing.md) - Test your criteria effectively
