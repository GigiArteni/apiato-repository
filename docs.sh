#!/bin/bash

# ========================================
# APIATO REPOSITORY DOCUMENTATION GENERATOR
# Generate comprehensive documentation for the package
# ========================================

echo "📚 Generating Comprehensive Documentation for Apiato Repository Package..."
echo ""

# Create docs directory
mkdir -p docs/images

echo "📝 Creating Installation Guide..."

cat > docs/installation.md << 'EOF'
# Installation Guide

Complete installation guide for Apiato Repository package.

## System Requirements

- PHP 8.1 or higher
- Laravel 11.0+ or 12.0+
- Composer 2.0+

## Installation Steps

### 1. Install via Composer

```bash
composer require apiato/repository
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=repository-config
```

This will create `config/repository.php` with all configuration options.

### 3. Environment Configuration

Add these variables to your `.env` file:

```env
# Repository Cache Settings
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=60
REPOSITORY_CACHE_STORE=redis
REPOSITORY_CACHE_CLEAR_ON_WRITE=true

# HashId Settings (for Apiato integration)
HASHID_ENABLED=true
APIATO_ENABLED=true
```

## Apiato Integration

### For Existing Apiato Projects

If you're upgrading from `l5-repository`, the package is designed as a drop-in replacement:

```bash
# Remove old package
composer remove prettus/l5-repository

# Install new package
composer require apiato/repository
```

### Directory Structure

The package follows Apiato's Porto SAP architecture:

```
app/
├── Containers/
│   └── User/
│       └── Data/
│           ├── Repositories/
│           │   ├── UserRepository.php
│           │   └── UserRepositoryInterface.php
│           ├── Criteria/
│           │   └── ActiveUsersCriteria.php
│           └── Validators/
│               └── UserValidator.php
├── Ship/
│   └── Parents/
│       ├── Models/
│       ├── Repositories/
│       └── Criteria/
```

## Configuration Overview

Key configuration sections in `config/repository.php`:

### Generator Settings

```php
'generator' => [
    'basePath' => app_path(),
    'rootNamespace' => 'App\\',
    'paths' => [
        'models' => 'Ship/Parents/Models',
        'repositories' => 'Containers/{container}/Data/Repositories',
        'criteria' => 'Containers/{container}/Data/Criteria',
        'presenters' => 'Containers/{container}/UI/API/Transformers',
    ],
],
```

### Cache Configuration

```php
'cache' => [
    'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
    'minutes' => env('REPOSITORY_CACHE_MINUTES', 60),
    'store' => env('REPOSITORY_CACHE_STORE', 'default'),
    'clear_on_write' => env('REPOSITORY_CACHE_CLEAR_ON_WRITE', true),
],
```

### HashId Integration

```php
'hashid' => [
    'enabled' => env('HASHID_ENABLED', true),
    'auto_detect' => true,
    'auto_encode' => true,
    'fields' => ['id', '*_id'],
],
```

## Verification

Test your installation:

```bash
# Generate a test repository
php artisan make:repository TestRepository --model=User

# Clear cache
php artisan repository:clear-cache

# Run tests (if available)
php artisan test
```

## Laravel Service Container

The package automatically registers with Laravel's service container. You can inject repositories into your controllers:

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function index()
    {
        return $this->userRepository->paginate();
    }
}
```

## Troubleshooting

### Common Issues

**1. Class not found errors**
```bash
composer dump-autoload
```

**2. Cache issues**
```bash
php artisan config:clear
php artisan cache:clear
php artisan repository:clear-cache
```

**3. HashId integration not working**
```bash
# Ensure hashids/hashids is installed
composer require hashids/hashids

# Check Apiato HashId configuration
php artisan config:show apiato.hash-id
```

**4. Fractal serialization issues**
```bash
# Ensure league/fractal is installed
composer require league/fractal
```

## Next Steps

- [Repository Usage](repositories.md) - Learn basic repository operations
- [Criteria System](criteria.md) - Advanced query building
- [Caching Strategy](caching.md) - Optimize performance
- [HashId Integration](hashids.md) - Secure ID handling
- [Fractal Presenters](presenters.md) - Data transformation
- [Testing Guide](testing.md) - Test your repositories

## Support

- **Issues**: [GitHub Issues](https://github.com/apiato/repository/issues)
- **Documentation**: [Full Documentation](https://apiato.io/docs/components/repository)
- **Community**: [Apiato Discord](https://discord.gg/apiato)
EOF

echo "📝 Creating Repository Usage Guide..."

cat > docs/repositories.md << 'EOF'
# Repository Usage Guide

Learn how to create and use repositories with the Apiato Repository package.

## Creating Repositories

### Basic Repository

```bash
php artisan make:repository UserRepository --model=User
```

This generates:

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Apiato\Repository\Eloquent\BaseRepository;

class UserRepository extends BaseRepository
{
    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => 'in',
    ];

    public function model(): string
    {
        return User::class;
    }
}
```

### Advanced Repository with Features

```bash
php artisan make:repository UserRepository --model=User --cache --interface
```

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Apiato\Repository\Contracts\CacheableInterface;
use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\CacheableRepository;
use Apiato\Repository\Traits\HashIdRepository;

class UserRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository, HashIdRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'created_at' => 'date_between',
        'role_id' => 'in',
    ];
    
    protected int $cacheMinutes = 120;
    protected array $cacheTags = ['users'];

    public function model(): string
    {
        return User::class;
    }
}
```

## Basic Operations

### Create

```php
// Basic create
$user = $this->userRepository->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password')
]);

// With validation (if validator is configured)
$user = $this->userRepository->create($validatedData);
```

### Read Operations

```php
// Find by ID
$user = $this->userRepository->find(1);
$user = $this->userRepository->findOrFail(1);

// Find by field
$users = $this->userRepository->findByField('status', 'active');
$user = $this->userRepository->findByField('email', 'john@example.com')->first();

// Find with conditions
$users = $this->userRepository->findWhere([
    'status' => 'active',
    ['created_at', '>=', '2024-01-01']
]);

// Find first matching
$user = $this->userRepository->findWhereFirst([
    'email' => 'john@example.com'
]);

// Find with IN conditions
$users = $this->userRepository->findWhereIn('id', [1, 2, 3, 4, 5]);
$users = $this->userRepository->findWhereNotIn('status', ['banned', 'suspended']);

// Find between values
$users = $this->userRepository->findWhereBetween('created_at', [
    '2024-01-01', '2024-12-31'
]);
```

### Update

```php
// Update by ID
$user = $this->userRepository->update([
    'name' => 'Jane Doe'
], 1);

// Update or create
$user = $this->userRepository->updateOrCreate([
    'email' => 'john@example.com'
], [
    'name' => 'John Doe',
    'status' => 'active'
]);
```

### Delete

```php
// Delete by ID
$deleted = $this->userRepository->delete(1);

// Delete multiple
$deleted = $this->userRepository->deleteMultiple([1, 2, 3]);

// Delete with conditions
$deleted = $this->userRepository->deleteWhere([
    'status' => 'inactive',
    ['last_login', '<', now()->subDays(30)]
]);
```

### Pagination

```php
// Basic pagination
$users = $this->userRepository->paginate(15);

// Custom pagination
$users = $this->userRepository->paginate(
    perPage: 20,
    columns: ['id', 'name', 'email'],
    pageName: 'page',
    page: 2
);

// Get all without pagination
$users = $this->userRepository->all(['id', 'name', 'email']);
```

## Field Searchable Configuration

Configure which fields can be searched and how:

```php
protected array $fieldSearchable = [
    // Basic operators
    'name' => 'like',           // LIKE search
    'email' => '=',             // Exact match
    'status' => 'in',           // IN operator
    
    // Date operators
    'created_at' => 'date_between',
    'updated_at' => 'date_equals',
    
    // Number operators
    'age' => 'between',
    'salary' => 'number_range',
    
    // Relationship searches
    'profile.bio' => 'like',
    'posts.title' => 'like',
    
    // Multiple operators for same field
    'price' => ['=', '>', '<', 'between'],
];
```

### Available Search Operators

```php
// Comparison operators
'=' | '!=' | '<>' | '>' | '<' | '>=' | '<='

// String operators
'like' | 'ilike' | 'not_like'

// Array operators
'in' | 'not_in' | 'notin'

// Range operators
'between' | 'not_between'

// Date operators
'date_between' | 'date_equals' | 'date_not_equals'
'today' | 'yesterday' | 'this_week' | 'last_week'
'this_month' | 'last_month' | 'this_year' | 'last_year'

// Number operators
'number_range' | 'number_between'

// Null operators
'null' | 'not_null' | 'notnull'
```

## Advanced Query Building

### Using Query Builder

```php
// Get the query builder
$query = $this->userRepository->query();

// Build complex queries
$users = $this->userRepository
    ->query()
    ->where('status', 'active')
    ->whereHas('posts', function ($query) {
        $query->where('published', true);
    })
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### Custom Repository Methods

Add custom methods to your repository:

```php
class UserRepository extends BaseRepository
{
    // ... base configuration ...

    /**
     * Find active users with posts
     */
    public function findActiveUsersWithPosts(): Collection
    {
        return $this->query()
            ->where('status', 'active')
            ->whereHas('posts')
            ->with(['posts' => function ($query) {
                $query->where('published', true);
            }])
            ->get();
    }

    /**
     * Get users by role
     */
    public function findByRole(string $role): Collection
    {
        return $this->findWhere([
            'role.name' => $role
        ]);
    }

    /**
     * Search users with filters
     */
    public function searchWithFilters(array $filters): LengthAwarePaginator
    {
        $query = $this->query();

        if (isset($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
}
```

## Repository with Presenter

```php
class UserRepository extends BaseRepository
{
    public function presenter(): string
    {
        return UserPresenter::class;
    }

    // Data will be automatically transformed through the presenter
    public function findWithPresenter($id)
    {
        return $this->find($id); // Returns transformed data
    }

    // Skip presenter when needed
    public function findRaw($id)
    {
        return $this->skipPresenter()->find($id);
    }
}
```

## Repository with Validator

```php
class UserRepository extends BaseRepository
{
    public function validator(): string
    {
        return UserValidator::class;
    }

    // Data will be automatically validated
    public function createUser(array $data)
    {
        // Validation happens automatically in create()
        return $this->create($data);
    }
}
```

## Error Handling

```php
use Apiato\Repository\Exceptions\RepositoryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

try {
    $user = $this->userRepository->findOrFail($id);
} catch (ModelNotFoundException $e) {
    // Handle not found
    return response()->json(['error' => 'User not found'], 404);
} catch (RepositoryException $e) {
    // Handle repository errors
    return response()->json(['error' => 'Repository error'], 500);
} catch (ValidationException $e) {
    // Handle validation errors
    return response()->json(['errors' => $e->errors()], 422);
}
```

## Best Practices

### 1. Repository Interface

Always create interfaces for your repositories:

```php
interface UserRepositoryInterface extends RepositoryInterface
{
    public function findActiveUsers(): Collection;
    public function findByRole(string $role): Collection;
}

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    // Implementation
}
```

### 2. Service Container Binding

Bind your interfaces in a service provider:

```php
$this->app->bind(UserRepositoryInterface::class, UserRepository::class);
```

### 3. Controller Injection

Use dependency injection in controllers:

```php
class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
}
```

### 4. Resource Controllers

Create clean resource controllers:

```php
class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function index(Request $request)
    {
        return $this->userRepository
            ->pushCriteria(new RequestCriteria($request))
            ->paginate();
    }

    public function show(string $id)
    {
        return $this->userRepository->findOrFail($id);
    }

    public function store(StoreUserRequest $request)
    {
        return $this->userRepository->create($request->validated());
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        return $this->userRepository->update($request->validated(), $id);
    }

    public function destroy(string $id)
    {
        $this->userRepository->delete($id);
        return response()->noContent();
    }
}
```

## Next Steps

- [Criteria System](criteria.md) - Advanced query building with criteria
- [Caching Strategy](caching.md) - Implement intelligent caching
- [HashId Integration](hashids.md) - Secure ID handling
- [Fractal Presenters](presenters.md) - Professional data transformation
EOF

echo "📝 Creating Criteria System Guide..."

cat > docs/criteria.md << 'EOF'
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
EOF

echo "📝 Creating Caching Strategy Guide..."

cat > docs/caching.md << 'EOF'
# Caching Strategy Guide

Learn how to implement intelligent caching with the Apiato Repository package for optimal performance.

## Overview

The caching system provides:
- **Tagged Cache Support** - Fine-grained cache invalidation
- **Automatic Cache Keys** - Intelligent key generation
- **Query-based Caching** - Cache based on criteria and parameters
- **Write-through Invalidation** - Automatic cache clearing on writes
- **Configurable Stores** - Redis, Memcached, or any Laravel cache store

## Basic Caching Setup

### Enable Caching in Repository

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Apiato\Repository\Contracts\CacheableInterface;
use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\CacheableRepository;

class UserRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => 'in',
    ];
    
    // Cache configuration
    protected int $cacheMinutes = 60;           // Cache for 1 hour
    protected array $cacheTags = ['users'];     // Cache tags
    
    // Optional: Specify which methods to cache
    protected array $cacheOnly = ['all', 'find', 'paginate'];
    
    // Optional: Specify methods to exclude from cache
    protected array $cacheExcept = ['create', 'update', 'delete'];

    public function model(): string
    {
        return User::class;
    }
}
```

### Configuration

Configure caching in `config/repository.php`:

```php
'cache' => [
    'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
    'minutes' => env('REPOSITORY_CACHE_MINUTES', 60),
    'store' => env('REPOSITORY_CACHE_STORE', 'redis'),
    'clear_on_write' => env('REPOSITORY_CACHE_CLEAR_ON_WRITE', true),
    'skip_uri' => env('REPOSITORY_CACHE_SKIP_URI', 'skipCache'),
    'allowed_methods' => [
        'all', 'paginate', 'find', 'findOrFail', 'findByField',
        'findWhere', 'findWhereFirst', 'findWhereIn', 'findWhereNotIn',
        'findWhereBetween'
    ],
],
```

### Environment Configuration

```env
# Cache Settings
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=60
REPOSITORY_CACHE_STORE=redis
REPOSITORY_CACHE_CLEAR_ON_WRITE=true

# Redis Configuration (recommended)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

## Using Cached Repositories

### Basic Usage

```php
// These operations are automatically cached
$users = $this->userRepository->all();                    // Cached for 60 minutes
$user = $this->userRepository->find(1);                   // Cached for 60 minutes
$users = $this->userRepository->paginate(15);             // Cached for 60 minutes

// Write operations automatically clear cache
$user = $this->userRepository->create([...]);             // Clears 'users' cache tag
$user = $this->userRepository->update([...], 1);          // Clears 'users' cache tag
$this->userRepository->delete(1);                         // Clears 'users' cache tag
```

### Dynamic Cache Control

```php
// Set custom cache duration
$users = $this->userRepository
    ->cacheMinutes(120)  // Cache for 2 hours
    ->all();

// Set custom cache key
$users = $this->userRepository
    ->cacheKey('premium_users')
    ->findWhere(['type' => 'premium']);

// Skip cache for this operation
$users = $this->userRepository
    ->skipCache()
    ->all();

// Chain cache settings
$users = $this->userRepository
    ->cacheMinutes(30)
    ->cacheKey('active_users')
    ->findWhere(['status' => 'active']);
```

## Advanced Caching Strategies

### Tagged Cache with Relationships

```php
class UserRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    protected array $cacheTags = ['users', 'profiles', 'posts'];

    // Cache will be tagged with all related models
    public function findWithProfile($id)
    {
        return $this->cacheResult('findWithProfile', [$id], function () use ($id) {
            return $this->query()
                ->with(['profile', 'posts'])
                ->find($id);
        });
    }
}

class PostRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    protected array $cacheTags = ['posts', 'users', 'categories'];

    // When posts are updated, it affects user and category caches too
}
```

### Hierarchical Cache Tags

```php
class ProductRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    protected function getCacheTags(): array
    {
        $tags = ['products'];
        
        // Add category-specific tags
        if ($categoryId = request('category_id')) {
            $tags[] = "category.{$categoryId}";
        }
        
        // Add brand-specific tags
        if ($brandId = request('brand_id')) {
            $tags[] = "brand.{$brandId}";
        }
        
        return $tags;
    }
}
```

### Criteria-aware Caching

```php
// Cache keys automatically include criteria
$users = $this->userRepository
    ->pushCriteria(new ActiveUsersCriteria())
    ->pushCriteria(new RecentUsersCriteria())
    ->cacheMinutes(30)
    ->all();

// Different criteria = different cache key
$premiumUsers = $this->userRepository
    ->clearCriteria()
    ->pushCriteria(new PremiumUsersCriteria())
    ->cacheMinutes(30)
    ->all();
```

### Request-based Caching

```php
use Apiato\Repository\Criteria\RequestCriteria;

public function index(Request $request)
{
    // Cache key includes all request parameters
    return $this->userRepository
        ->pushCriteria(new RequestCriteria($request))
        ->cacheMinutes(15) // Shorter cache for filtered results
        ->paginate();
}
```

## Cache Invalidation Strategies

### Automatic Invalidation

```php
// Write operations automatically clear cache
$user = $this->userRepository->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
// 'users' cache tag is automatically cleared
```

### Manual Cache Clearing

```php
// Clear all cache for this repository
$this->userRepository->clearCache();

// Clear specific cache tags
Cache::tags(['users'])->flush();

// Clear multiple tags
Cache::tags(['users', 'profiles'])->flush();

// Clear cache via artisan command
php artisan repository:clear-cache --tags=users,posts
```

### Selective Cache Clearing

```php
class UserRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    protected function clearCacheAfterWrite(): void
    {
        // Override to customize cache clearing
        if (config('repository.cache.clear_on_write', true)) {
            // Only clear user-specific caches, not all
            Cache::tags(['users'])->flush();
            
            // Don't clear global caches like 'statistics'
        }
    }

    public function updateProfile($userId, array $data)
    {
        $user = $this->update($data, $userId);
        
        // Clear specific user cache
        Cache::tags(["user.{$userId}"])->flush();
        
        return $user;
    }
}
```

### Event-based Cache Clearing

```php
// In your EventServiceProvider
protected $listen = [
    'user.created' => [ClearUserCache::class],
    'user.updated' => [ClearUserCache::class],
    'user.deleted' => [ClearUserCache::class],
    'post.published' => [ClearPostCache::class, ClearUserCache::class],
];

// Cache clearing listener
class ClearUserCache
{
    public function handle($event)
    {
        Cache::tags(['users'])->flush();
        
        if (isset($event->user)) {
            Cache::tags(["user.{$event->user->id}"])->flush();
        }
    }
}
```

## Performance Optimization

### Cache Warming

```php
// Artisan command to warm cache
class WarmRepositoryCache extends Command
{
    protected $signature = 'cache:warm-repositories';

    public function handle()
    {
        // Warm frequently accessed data
        $this->userRepository->cacheMinutes(240)->all();
        $this->productRepository->cacheMinutes(120)->findWhere(['featured' => true]);
        
        $this->info('Repository cache warmed successfully');
    }
}
```

### Intelligent Cache Keys

```php
class UserRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    public function getCacheKey(string $method, array $args = []): string
    {
        // Custom cache key generation
        $baseKey = parent::getCacheKey($method, $args);
        
        // Add user context
        if ($userId = auth()->id()) {
            $baseKey .= ".user.{$userId}";
        }
        
        // Add locale context
        if ($locale = app()->getLocale()) {
            $baseKey .= ".locale.{$locale}";
        }
        
        return $baseKey;
    }
}
```

### Cache Statistics and Monitoring

```php
class CacheMonitoringRepository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    protected function cacheResult(string $method, array $args, callable $callback): mixed
    {
        $start = microtime(true);
        $key = $this->getCacheKey($method, $args);
        
        $result = parent::cacheResult($method, $args, $callback);
        
        $duration = microtime(true) - $start;
        
        // Log cache performance
        Log::info('Cache operation', [
            'method' => $method,
            'key' => $key,
            'duration' => $duration,
            'hit' => Cache::has($key),
        ]);
        
        return $result;
    }
}
```

## Cache Configuration per Environment

### Production Configuration

```php
// config/repository.php (production)
'cache' => [
    'enabled' => true,
    'minutes' => 120,           // Longer cache in production
    'store' => 'redis',         // Use Redis for production
    'clear_on_write' => true,
],
```

### Development Configuration

```php
// config/repository.php (local)
'cache' => [
    'enabled' => false,         // Disable cache in development
    'minutes' => 5,             // Short cache for testing
    'store' => 'array',         // Use array store for testing
    'clear_on_write' => true,
],
```

### Testing Configuration

```php
// config/repository.php (testing)
'cache' => [
    'enabled' => false,         // Always disable in tests
    'minutes' => 1,
    'store' => 'array',
    'clear_on_write' => true,
],
```

## Cache with Queue Jobs

### Background Cache Warming

```php
class WarmCacheJob implements ShouldQueue
{
    public function __construct(
        private string $repository,
        private string $method,
        private array $args = []
    ) {}

    public function handle()
    {
        $repository = app($this->repository);
        
        // Warm cache in background
        $repository->cacheMinutes(240)->{$this->method}(...$this->args);
    }
}

// Dispatch cache warming jobs
dispatch(new WarmCacheJob(UserRepository::class, 'all'));
dispatch(new WarmCacheJob(ProductRepository::class, 'findWhere', [['featured' => true]]));
```

### Intelligent Cache Refresh

```php
class RefreshExpiredCacheJob implements ShouldQueue
{
    public function handle()
    {
        $expiredKeys = Cache::store('redis')->connection()->keys('repository.*expired*');
        
        foreach ($expiredKeys as $key) {
            // Parse key to determine repository and method
            [$repository, $method, $args] = $this->parseKey($key);
            
            // Refresh cache
            app($repository)->cacheMinutes(60)->{$method}(...$args);
        }
    }
}
```

## Testing Cache Behavior

### Unit Tests

```php
use Illuminate\Support\Facades\Cache;

class UserRepositoryCacheTest extends TestCase
{
    public function test_repository_caches_results(): void
    {
        Cache::shouldReceive('tags')
            ->with(['users'])
            ->andReturnSelf();
            
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([]));

        $this->userRepository->all();
    }

    public function test_cache_is_cleared_on_write(): void
    {
        Cache::shouldReceive('tags')
            ->with(['users'])
            ->andReturnSelf();
            
        Cache::shouldReceive('flush')
            ->once();

        $this->userRepository->create(['name' => 'Test']);
    }
}
```

### Integration Tests

```php
class UserRepositoryCacheIntegrationTest extends TestCase
{
    public function test_cached_repository_performance(): void
    {
        User::factory()->count(1000)->create();

        // First call - should hit database
        $start = microtime(true);
        $users = $this->userRepository->all();
        $firstCallTime = microtime(true) - $start;

        // Second call - should hit cache
        $start = microtime(true);
        $cachedUsers = $this->userRepository->all();
        $secondCallTime = microtime(true) - $start;

        $this->assertTrue($secondCallTime < $firstCallTime / 2);
        $this->assertEquals($users->count(), $cachedUsers->count());
    }
}
```

## Best Practices

### 1. Cache Granularity

```php
// Good: Specific cache tags
protected array $cacheTags = ['users', 'user_profiles'];

// Better: Include entity-specific tags
protected function getCacheTags(): array
{
    return ['users', 'user_profiles', "tenant.{$this->getCurrentTenantId()}"];
}
```

### 2. Cache Duration Strategy

```php
// Different cache durations for different data types
class CacheConfig
{
    const STATIC_DATA = 1440;      // 24 hours - rarely changes
    const USER_DATA = 60;          // 1 hour - changes moderately  
    const SEARCH_RESULTS = 15;     // 15 minutes - changes frequently
    const REAL_TIME_DATA = 1;      // 1 minute - changes constantly
}
```

### 3. Memory-conscious Caching

```php
// Don't cache large datasets
public function getAllUsers()
{
    // Bad: Could cache thousands of records
    return $this->userRepository->all();
}

// Good: Cache paginated results
public function getUsersPaginated($page = 1)
{
    return $this->userRepository
        ->cacheMinutes(30)
        ->paginate(50, ['*'], 'page', $page);
}
```

### 4. Cache Invalidation Patterns

```php
// Invalidate related caches when data changes
class UserService
{
    public function updateUserProfile($userId, array $data)
    {
        $user = $this->userRepository->update($data, $userId);
        
        // Clear related caches
        Cache::tags([
            'users',
            "user.{$userId}",
            'user_profiles',
            'user_statistics'
        ])->flush();
        
        return $user;
    }
}
```

## Monitoring and Debugging

### Cache Hit Rate Monitoring

```php
class CacheMetrics
{
    public static function trackCacheHit(string $key): void
    {
        Redis::hincrby('cache_metrics', 'hits', 1);
        Redis::hincrby('cache_metrics', "hit:{$key}", 1);
    }

    public static function trackCacheMiss(string $key): void
    {
        Redis::hincrby('cache_metrics', 'misses', 1);
        Redis::hincrby('cache_metrics', "miss:{$key}", 1);
    }

    public static function getCacheHitRate(): float
    {
        $hits = Redis::hget('cache_metrics', 'hits') ?: 0;
        $misses = Redis::hget('cache_metrics', 'misses') ?: 0;
        
        $total = $hits + $misses;
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }
}
```

### Debug Cache Keys

```bash
# View cache keys in Redis
redis-cli KEYS "repository.*"

# Monitor cache operations
redis-cli MONITOR | grep repository

# Get cache statistics
php artisan tinker
>>> Cache::getRedis()->info('memory')
```

## Next Steps

- [HashId Integration](hashids.md) - Secure caching with HashIds
- [Fractal Presenters](presenters.md) - Cache transformed data
- [Testing Guide](testing.md) - Test your caching strategy
EOF

echo "📝 Creating HashId Integration Guide..."

cat > docs/hashids.md << 'EOF'
# HashId Integration Guide

Learn how to implement secure, user-friendly ID handling with HashIds in your repositories.

## What are HashIds?

HashIds encode numeric IDs into short, unique, non-sequential strings. Instead of exposing database IDs like `123`, you get user-friendly IDs like `gY6N8`.

**Benefits:**
- **Security**: Hide actual database IDs
- **User-friendly**: Short, memorable identifiers  
- **Non-sequential**: Prevents ID guessing attacks
- **Reversible**: Can decode back to original ID
- **URL-safe**: Perfect for REST APIs

## Installation & Setup

### Install HashIds

```bash
composer require hashids/hashids
```

### Apiato Integration

For Apiato projects, HashIds are typically pre-configured. Check your configuration:

```php
// config/apiato.php
'hash-id' => [
    'salt' => env('APP_KEY'),
    'length' => 6,
    'chars' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
],
```

### Repository Configuration

Configure HashId support in `config/repository.php`:

```php
'hashid' => [
    'enabled' => env('HASHID_ENABLED', true),
    'auto_detect' => true,               // Auto-detect HashIds in requests
    'auto_encode' => true,               // Auto-encode IDs in responses
    'min_length' => 4,                   // Minimum HashId length
    'max_length' => 20,                  // Maximum HashId length
    'fields' => ['id', '*_id'],          // Fields to process
    'fallback_to_numeric' => true,       // Fall back to numeric if decode fails
    'cache_decoded_ids' => true,         // Cache decoded IDs
],
```

## Basic HashId Repository Usage

### Enable HashId Support

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\HashIdRepository;

class UserRepository extends BaseRepository
{
    use HashIdRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'role_id' => 'in',  // HashIds work with foreign keys too
    ];

    public function model(): string
    {
        return User::class;
    }
}
```

### Basic Operations with HashIds

```php
// Find by HashId
$user = $this->userRepository->findByHashId('gY6N8');

// Find or fail by HashId
$user = $this->userRepository->findByHashIdOrFail('gY6N8');

// Update by HashId
$user = $this->userRepository->updateByHashId([
    'name' => 'Updated Name'
], 'gY6N8');

// Delete by HashId
$deleted = $this->userRepository->deleteByHashId('gY6N8');

// Encode/Decode manually
$hashId = $this->userRepository->encodeHashId(123);    // Returns: "gY6N8"
$id = $this->userRepository->decodeHashId('gY6N8');    // Returns: 123
```

## API Integration

### Controller with HashIds

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function show(string $hashId)
    {
        // Works seamlessly with HashIds
        $user = $this->userRepository->findByHashIdOrFail($hashId);
        return response()->json($user);
    }

    public function update(Request $request, string $hashId)
    {
        $user = $this->userRepository->updateByHashId(
            $request->validated(),
            $hashId
        );
        return response()->json($user);
    }

    public function destroy(string $hashId)
    {
        $this->userRepository->deleteByHashId($hashId);
        return response()->noContent();
    }
}
```

### Route Model Binding with HashIds

```php
// In your RouteServiceProvider or web.php
Route::bind('user', function ($hashId) {
    return app(UserRepository::class)->findByHashIdOrFail($hashId);
});

// Now you can use route model binding
Route::get('/users/{user}', [UserController::class, 'show']);

// Controller method
public function show(User $user)
{
    return response()->json($user);
}
```

## Advanced HashId Features

### Request Criteria with HashId Support

The `RequestCriteria` automatically handles HashIds:

```php
use Apiato\Repository\Criteria\RequestCriteria;

public function index(Request $request)
{
    // These requests work automatically with HashIds:
    // /api/users?search=id:gY6N8
    // /api/users?filter=role_id:in:abc123,def456
    // /api/posts?search=user_id:gY6N8
    
    return $this->userRepository
        ->pushCriteria(new RequestCriteria($request))
        ->paginate();
}
```

### Supported HashId Query Examples

```bash
# Find by HashId
GET /api/users?search=id:gY6N8

# Multiple HashIds
GET /api/users?search=id:in:gY6N8,kL9M2,pQ4R7

# Foreign key HashIds
GET /api/posts?filter=user_id:gY6N8
GET /api/comments?filter=post_id:in:abc123,def456

# Mixed with other filters
GET /api/users?search=name:like:john;role_id:gY6N8&searchJoin=and
```

### HashId-aware Transformers

```php
<?php

namespace App\Transformers;

use Apiato\Repository\Presenters\BaseTransformer;

class UserTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['posts', 'profile'];

    public function transform($user): array
    {
        // BaseTransformer automatically handles HashId encoding
        return $this->encodeHashIds([
            'id' => $user->id,                    // Encoded to HashId
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,          // Encoded to HashId
            'created_at' => $user->created_at->toISOString(),
        ]);
    }

    public function includePosts($user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }
}
```

### Response with HashIds

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com",
    "role_id": "kL9M2",
    "created_at": "2024-01-15T10:30:00Z",
    "posts": [
      {
        "id": "pQ4R7",
        "title": "My First Post",
        "user_id": "gY6N8"
      }
    ]
  }
}
```

## Custom HashId Implementation

### Custom HashId Configuration

```php
class UserRepository extends BaseRepository
{
    use HashIdRepository;

    protected function initializeHashIds(): void
    {
        // Custom HashIds configuration
        $this->hashIds = new \Hashids\Hashids(
            salt: config('app.key') . '_users',  // User-specific salt
            minHashLength: 8,                    // Longer HashIds
            alphabet: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'  // Uppercase only
        );
    }
}
```

### Model-specific HashIds

```php
class PostRepository extends BaseRepository
{
    use HashIdRepository;

    protected function initializeHashIds(): void
    {
        // Posts get different HashIds than users
        $this->hashIds = new \Hashids\Hashids(
            salt: config('app.key') . '_posts',
            minHashLength: 6,
            alphabet: 'abcdefghijklmnopqrstuvwxyz1234567890'
        );
    }
}
```

### Contextual HashIds

```php
class MultiTenantRepository extends BaseRepository
{
    use HashIdRepository;

    protected function initializeHashIds(): void
    {
        $tenantId = auth()->user()?->tenant_id ?? 'default';
        
        $this->hashIds = new \Hashids\Hashids(
            salt: config('app.key') . "_{$tenantId}",
            minHashLength: 6
        );
    }
}
```

## Error Handling

### Invalid HashIds

```php
class UserController extends Controller
{
    public function show(string $hashId)
    {
        try {
            $user = $this->userRepository->findByHashIdOrFail($hashId);
            return response()->json($user);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'message' => 'The provided ID is invalid or user does not exist'
            ], 404);
        }
    }
}
```

### Graceful Fallback

```php
class HashIdService
{
    public function findUser(string $identifier): ?User
    {
        // Try HashId first
        if ($this->looksLikeHashId($identifier)) {
            $id = $this->userRepository->decodeHashId($identifier);
            if ($id) {
                return $this->userRepository->find($id);
            }
        }
        
        // Fallback to numeric ID (if allowed)
        if (is_numeric($identifier) && config('repository.hashid.fallback_to_numeric')) {
            return $this->userRepository->find((int)$identifier);
        }
        
        return null;
    }
}
```

## Performance Optimization

### Cache Decoded HashIds

```php
class OptimizedHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    protected array $decodedCache = [];

    public function decodeHashId(string $hashId): ?int
    {
        // Check cache first
        if (isset($this->decodedCache[$hashId])) {
            return $this->decodedCache[$hashId];
        }

        $decoded = parent::decodeHashId($hashId);
        
        // Cache the result
        if ($decoded && config('repository.hashid.cache_decoded_ids')) {
            $this->decodedCache[$hashId] = $decoded;
        }

        return $decoded;
    }
}
```

### Batch HashId Operations

```php
class BatchHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    public function findByHashIds(array $hashIds): Collection
    {
        $decodedIds = array_filter(array_map([$this, 'decodeHashId'], $hashIds));
        return $this->findWhereIn('id', $decodedIds);
    }

    public function encodeModelIds(Collection $models): Collection
    {
        return $models->map(function ($model) {
            $model->hashid = $this->encodeHashId($model->id);
            return $model;
        });
    }
}
```

### Redis Cache for HashIds

```php
class CachedHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    public function decodeHashId(string $hashId): ?int
    {
        $cacheKey = "hashid:decode:{$hashId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($hashId) {
            return parent::decodeHashId($hashId);
        });
    }

    public function encodeHashId(int $id): string
    {
        $cacheKey = "hashid:encode:{$id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return parent::encodeHashId($id);
        });
    }
}
```

## Security Considerations

### HashId Salt Management

```php
// .env - Use strong, unique salts
HASHID_SALT_USERS="${APP_KEY}_users_2024"
HASHID_SALT_POSTS="${APP_KEY}_posts_2024"
HASHID_SALT_ORDERS="${APP_KEY}_orders_2024"
```

### Permission-based HashIds

```php
class SecureUserRepository extends BaseRepository
{
    use HashIdRepository;

    public function findByHashIdOrFail(string $hashId): Model
    {
        $user = parent::findByHashIdOrFail($hashId);
        
        // Check permissions
        if (!$this->canAccessUser($user)) {
            throw new AuthorizationException('Access denied');
        }
        
        return $user;
    }

    private function canAccessUser(User $user): bool
    {
        $currentUser = auth()->user();
        
        // Admin can access any user
        if ($currentUser->isAdmin()) {
            return true;
        }
        
        // Users can only access their own data
        return $currentUser->id === $user->id;
    }
}
```

### Rate Limiting HashId Decoding

```php
class RateLimitedHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    public function decodeHashId(string $hashId): ?int
    {
        $key = 'hashid_decode:' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 100)) {
            throw new TooManyRequestsException('Too many decode attempts');
        }
        
        RateLimiter::hit($key);
        
        return parent::decodeHashId($hashId);
    }
}
```

## Testing HashIds

### Unit Tests

```php
use Tests\TestCase;
use App\Repositories\UserRepository;

class HashIdRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_can_encode_and_decode_hash_id(): void
    {
        $id = 123;
        $hashId = $this->repository->encodeHashId($id);
        $decodedId = $this->repository->decodeHashId($hashId);

        $this->assertNotEquals($id, $hashId);
        $this->assertEquals($id, $decodedId);
    }

    public function test_can_find_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);
        
        $found = $this->repository->findByHashId($hashId);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_returns_null_for_invalid_hash_id(): void
    {
        $found = $this->repository->findByHashId('invalid');
        $this->assertNull($found);
    }
}
```

### Feature Tests

```php
class HashIdApiTest extends TestCase
{
    public function test_api_returns_hash_ids(): void
    {
        $user = User::factory()->create();
        
        $response = $this->getJson("/api/users/{$user->id}");
        
        $response->assertStatus(200);
        $this->assertNotEquals($user->id, $response->json('data.id'));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $response->json('data.id'));
    }

    public function test_can_access_user_by_hash_id(): void
    {
        $user = User::factory()->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);
        
        $response = $this->getJson("/api/users/{$hashId}");
        
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}
```

## Best Practices

### 1. Consistent HashId Usage

```php
// Good: Always use HashIds in API responses
class UserController extends Controller
{
    public function show(string $hashId)
    {
        return $this->userRepository->findByHashIdOrFail($hashId);
    }
}

// Bad: Mixing numeric IDs and HashIds
class InconsistentController extends Controller
{
    public function show($id)  // Unclear if HashId or numeric
    {
        return $this->userRepository->find($id);
    }
}
```

### 2. Model-specific Salts

```php
// Good: Different salts for different models
'hashid_salts' => [
    'users' => env('HASHID_SALT_USERS', env('APP_KEY') . '_users'),
    'posts' => env('HASHID_SALT_POSTS', env('APP_KEY') . '_posts'),
    'orders' => env('HASHID_SALT_ORDERS', env('APP_KEY') . '_orders'),
]

// Bad: Same salt for all models (security risk)
'hashid_salt' => env('APP_KEY')
```

### 3. Validation

```php
// Custom validation rule for HashIds
class HashIdRule implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }
        
        return app(UserRepository::class)->decodeHashId($value) !== null;
    }

    public function message()
    {
        return 'The :attribute is not a valid identifier.';
    }
}

// Use in form requests
class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => ['required', new HashIdRule()],
            'name' => 'required|string|max:255',
        ];
    }
}
```

### 4. Documentation

```php
/**
 * @OA\Get(
 *     path="/api/users/{id}",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="User HashId (e.g., 'gY6N8')",
 *         @OA\Schema(type="string", pattern="^[a-zA-Z0-9]+$")
 *     ),
 *     @OA\Response(response=200, description="User details")
 * )
 */
public function show(string $hashId) { }
```

## Troubleshooting

### Common Issues

**1. HashId decode returns null**
```bash
# Check HashId configuration
php artisan tinker
>>> app('hashids')->decode('gY6N8')

# Verify salt consistency
>>> config('apiato.hash-id.salt')
```

**2. Inconsistent HashIds**
```bash
# Different environments might have different salts
# Ensure APP_KEY is consistent across environments
php artisan key:generate --show
```

**3. Performance issues**
```bash
# Enable HashId caching
REPOSITORY_HASHID_CACHE_ENABLED=true

# Monitor decode operations
Log::info('HashId decode', ['hashid' => $hashId, 'decoded' => $decoded]);
```

## Next Steps

- [Fractal Presenters](presenters.md) - Transform data with HashIds
- [Testing Guide](testing.md) - Test HashId functionality
- [Caching Strategy](caching.md) - Cache with HashId awareness
EOF

echo "📝 Creating Fractal Presenters Guide..."

cat > docs/presenters.md << 'EOF'
# Fractal Presenters Guide

Learn how to implement professional data transformation using Fractal presenters with the Apiato Repository package.

## What are Fractal Presenters?

Fractal is a powerful data transformation layer that provides:
- **Consistent API responses** - Standardized data format
- **Include/Exclude relationships** - Client-controlled data loading
- **Data transformation** - Clean, formatted output
- **Pagination support** - Built-in pagination handling
- **HashId integration** - Automatic ID encoding
- **Resource optimization** - Load only what's needed

## Basic Setup

### Install Fractal

```bash
composer require league/fractal
```

### Create a Transformer

```bash
php artisan make:transformer UserTransformer
```

### Basic Transformer

```php
<?php

namespace App\Transformers;

use App\Models\User;
use Apiato\Repository\Presenters\BaseTransformer;

class UserTransformer extends BaseTransformer
{
    /**
     * Available relationships to include
     */
    protected array $availableIncludes = [
        'profile',
        'posts',
        'comments',
        'role'
    ];

    /**
     * Default relationships to include
     */
    protected array $defaultIncludes = [
        // 'profile'  // Uncomment to always include
    ];

    /**
     * Transform user data
     */
    public function transform(User $user): array
    {
        return $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ]);
    }

    /**
     * Include user profile
     */
    public function includeProfile(User $user)
    {
        if (!$user->profile) {
            return $this->null();
        }

        return $this->item($user->profile, new ProfileTransformer());
    }

    /**
     * Include user posts
     */
    public function includePosts(User $user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }

    /**
     * Include user role
     */
    public function includeRole(User $user)
    {
        if (!$user->role) {
            return $this->null();
        }

        return $this->item($user->role, new RoleTransformer());
    }
}
```

### Repository with Presenter

```php
<?php

namespace App\Repositories;

use App\Models\User;
use App\Presenters\UserPresenter;
use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\HashIdRepository;

class UserRepository extends BaseRepository
{
    use HashIdRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => 'in',
    ];

    public function model(): string
    {
        return User::class;
    }

    public function presenter(): string
    {
        return UserPresenter::class;
    }
}
```

### Create Presenter Class

```php
<?php

namespace App\Presenters;

use App\Transformers\UserTransformer;
use Apiato\Repository\Presenters\FractalPresenter;

class UserPresenter extends FractalPresenter
{
    public function __construct()
    {
        parent::__construct(app(\League\Fractal\Manager::class));
        $this->setTransformer(new UserTransformer());
    }
}
```

## API Usage Examples

### Basic API Responses

```php
// Controller
class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function index()
    {
        // Returns transformed data automatically
        return $this->userRepository->paginate(15);
    }

    public function show(string $hashId)
    {
        return $this->userRepository->findByHashIdOrFail($hashId);
    }
}
```

### API Response Examples

**Basic response:**
```bash
GET /api/users/gY6N8
```

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "status": "active",
    "email_verified_at": "2024-01-15T10:30:00Z",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

**With includes:**
```bash
GET /api/users/gY6N8?include=profile,posts
```

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com",
    "profile": {
      "data": {
        "id": "kL9M2",
        "bio": "Software developer",
        "avatar": "https://example.com/avatar.jpg",
        "country": "USA"
      }
    },
    "posts": {
      "data": [
        {
          "id": "pQ4R7",
          "title": "My First Post",
          "slug": "my-first-post",
          "excerpt": "This is my first post...",
          "created_at": "2024-01-16T14:20:00Z"
        }
      ]
    }
  }
}
```

**Paginated response:**
```bash
GET /api/users?include=profile
```

```json
{
  "data": [
    {
      "id": "gY6N8",
      "name": "John Doe",
      "profile": {
        "data": {
          "id": "kL9M2",
          "bio": "Software developer"
        }
      }
    }
  ],
  "meta": {
    "pagination": {
      "total": 150,
      "per_page": 15,
      "current_page": 1,
      "last_page": 10,
      "from": 1,
      "to": 15,
      "path": "http://api.example.com/users",
      "next_page_url": "http://api.example.com/users?page=2",
      "prev_page_url": null
    }
  }
}
```

## Advanced Transformer Features

### Conditional Fields

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString(),
        ]);

        // Add admin-only fields
        if (auth()->user()?->isAdmin()) {
            $data['internal_notes'] = $user->internal_notes;
            $data['last_login_ip'] = $user->last_login_ip;
        }

        // Add owner-only fields
        if (auth()->id() === $user->id) {
            $data['email_verified_at'] = $user->email_verified_at?->toISOString();
            $data['two_factor_enabled'] = $user->two_factor_enabled;
        }

        return $data;
    }
}
```

### Dynamic Transformers

```php
class PostTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['author', 'comments', 'tags', 'category'];

    public function transform(Post $post): array
    {
        $data = $this->encodeHashIds([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status,
            'published_at' => $post->published_at?->toISOString(),
            'created_at' => $post->created_at->toISOString(),
        ]);

        // Add content based on status
        if ($post->status === 'published' || auth()->user()?->can('view', $post)) {
            $data['content'] = $post->content;
            $data['excerpt'] = $post->excerpt;
        } else {
            $data['excerpt'] = 'This post is not available.';
        }

        return $data;
    }

    public function includeAuthor(Post $post)
    {
        return $this->item($post->user, new UserTransformer());
    }

    public function includeComments(Post $post)
    {
        // Only include published comments
        $publishedComments = $post->comments()->where('status', 'approved')->get();
        return $this->collection($publishedComments, new CommentTransformer());
    }

    public function includeTags(Post $post)
    {
        return $this->collection($post->tags, new TagTransformer());
    }
}
```

### Nested Includes

```php
class OrderTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['items', 'customer', 'shipping_address'];

    public function transform(Order $order): array
    {
        return $this->encodeHashIds([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->created_at->toISOString(),
        ]);
    }

    public function includeItems(Order $order)
    {
        return $this->collection($order->items, new OrderItemTransformer());
    }

    public function includeCustomer(Order $order)
    {
        return $this->item($order->customer, new CustomerTransformer());
    }
}

class OrderItemTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['product', 'product.category'];

    public function transform(OrderItem $item): array
    {
        return $this->encodeHashIds([
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'total_price' => $item->total_price,
        ]);
    }

    public function includeProduct(OrderItem $item)
    {
        return $this->item($item->product, new ProductTransformer());
    }
}
```

**Usage with nested includes:**
```bash
GET /api/orders?include=items.product.category,customer
```

### Count Relationships

```php
class UserTransformer extends BaseTransformer
{
    protected array $availableIncludes = [
        'posts',
        'posts_count',
        'comments_count',
        'followers_count'
    ];

    public function includePostsCount(User $user)
    {
        return $this->primitive($user->posts()->count());
    }

    public function includeCommentsCount(User $user)
    {
        return $this->primitive($user->comments()->count());
    }

    public function includeFollowersCount(User $user)
    {
        return $this->primitive($user->followers()->count());
    }
}
```

**Usage:**
```bash
GET /api/users?include=posts_count,comments_count,followers_count
```

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "posts_count": 25,
    "comments_count": 147,
    "followers_count": 532
  }
}
```

## Sparse Fieldsets

### Enable Sparse Fieldsets

Configure in `config/repository.php`:

```php
'fractal' => [
    'params' => [
        'include' => 'include',
        'exclude' => 'exclude',
        'fields' => 'fields',    // Enable sparse fieldsets
    ],
],
```

### Usage Examples

```bash
# Only specific fields
GET /api/users?fields=id,name,email

# Fields with includes
GET /api/users?fields=id,name,posts&include=posts
GET /api/posts?fields[posts]=id,title,slug&fields[users]=id,name&include=author
```

### Transformer Support

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        // Full data set
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'created_at' => $user->created_at->toISOString(),
        ]);

        // Fractal will automatically filter fields based on request
        return $data;
    }
}
```

## Performance Optimization

### Eager Loading with Includes

```php
class UserRepository extends BaseRepository
{
    public function findWithIncludes(string $hashId, array $includes = []): ?Model
    {
        $id = $this->decodeHashId($hashId);
        
        if (!$id) {
            return null;
        }

        $query = $this->query();

        // Optimize based on requested includes
        if (in_array('profile', $includes)) {
            $query->with('profile');
        }

        if (in_array('posts', $includes)) {
            $query->with(['posts' => function ($q) {
                $q->where('status', 'published')
                  ->latest()
                  ->limit(10);
            }]);
        }

        if (in_array('posts_count', $includes)) {
            $query->withCount('posts');
        }

        return $query->find($id);
    }
}
```

### Lazy Loading Control

```php
class PostTransformer extends BaseTransformer
{
    public function includeComments(Post $post)
    {
        // Only load comments if not already loaded
        if (!$post->relationLoaded('comments')) {
            $post->load(['comments' => function ($query) {
                $query->where('status', 'approved')
                      ->with('author:id,name')
                      ->latest()
                      ->limit(5);
            }]);
        }

        return $this->collection($post->comments, new CommentTransformer());
    }
}
```

### Caching Transformed Data

```php
class CachedUserPresenter extends FractalPresenter
{
    public function present(mixed $data): mixed
    {
        if ($data instanceof Model) {
            $cacheKey = "transformed_user_{$data->id}_" . md5(serialize(request()->query()));
            
            return Cache::remember($cacheKey, 300, function () use ($data) {
                return parent::present($data);
            });
        }

        return parent::present($data);
    }
}
```

## Custom Serializers

### Create Custom Serializer

```php
<?php

namespace App\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class ApiSerializer extends ArraySerializer
{
    public function collection(?string $resourceKey, array $data): array
    {
        return [
            'data' => $data,
            'status' => 'success',
            'timestamp' => now()->toISOString(),
        ];
    }

    public function item(?string $resourceKey, array $data): array
    {
        return [
            'data' => $data,
            'status' => 'success',
            'timestamp' => now()->toISOString(),
        ];
    }

    public function null(): array
    {
        return [
            'data' => null,
            'status' => 'success',
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

### Configure Custom Serializer

```php
// config/repository.php
'fractal' => [
    'serializer' => App\Serializers\ApiSerializer::class,
],
```

### API Response with Custom Serializer

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com"
  },
  "status": "success",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

## Error Handling

### Transformer Exceptions

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        try {
            return $this->encodeHashIds([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $this->getAvatarUrl($user),
                'created_at' => $user->created_at->toISOString(),
            ]);
        } catch (\Exception $e) {
            // Log error and return safe fallback
            Log::error('User transformation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->encodeHashIds([
                'id' => $user->id,
                'name' => $user->name ?: 'Unknown User',
                'email' => $user->email,
                'avatar' => null,
                'created_at' => $user->created_at->toISOString(),
            ]);
        }
    }

    private function getAvatarUrl(User $user): ?string
    {
        if (!$user->avatar) {
            return null;
        }

        return Storage::disk('public')->url($user->avatar);
    }
}
```

### Missing Relationship Handling

```php
class PostTransformer extends BaseTransformer
{
    public function includeAuthor(Post $post)
    {
        // Handle soft-deleted users
        if (!$post->author || $post->author->trashed()) {
            return $this->item((object)[
                'id' => null,
                'name' => 'Deleted User',
                'email' => null,
            ], function ($data) {
                return [
                    'id' => null,
                    'name' => $data->name,
                    'email' => null,
                ];
            });
        }

        return $this->item($post->author, new UserTransformer());
    }
}
```

## Testing Transformers

### Unit Tests

```php
use Tests\TestCase;
use App\Models\User;
use App\Transformers\UserTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UserTransformerTest extends TestCase
{
    public function test_transforms_user_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $transformer = new UserTransformer();
        $result = $transformer->transform($user);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_encodes_hash_ids(): void
    {
        $user = User::factory()->create();
        $transformer = new UserTransformer();
        $result = $transformer->transform($user);

        $this->assertNotEquals($user->id, $result['id']);
        $this->assertIsString($result['id']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result['id']);
    }

    public function test_includes_profile_relationship(): void
    {
        $user = User::factory()->hasProfile()->create();
        
        $manager = new Manager();
        $manager->parseIncludes(['profile']);
        
        $resource = new Item($user, new UserTransformer());
        $result = $manager->createData($resource)->toArray();

        $this->assertArrayHasKey('profile', $result['data']);
        $this->assertArrayHasKey('data', $result['data']['profile']);
    }
}
```

### Integration Tests

```php
class UserApiTransformationTest extends TestCase
{
    public function test_api_returns_transformed_user_data(): void
    {
        $user = User::factory()->create();
        
        $response = $this->getJson("/api/users/{$user->hashid}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]
        ]);

        // Ensure ID is HashId, not numeric
        $this->assertNotEquals($user->id, $response->json('data.id'));
    }

    public function test_api_includes_work_correctly(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(3)->create();
        
        $response = $this->getJson("/api/users/{$user->hashid}?include=profile,posts");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'profile' => ['data'],
                'posts' => ['data' => [['id', 'title']]],
            ]
        ]);
    }
}
```

## Best Practices

### 1. Consistent Transformation

```php
// Good: Consistent field naming and structure
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        return $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString(),  // Consistent date format
            'updated_at' => $user->updated_at->toISOString(),
        ]);
    }
}

// Bad: Inconsistent field naming
class InconsistentTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        return [
            'user_id' => $user->id,      // Inconsistent with 'id'
            'userName' => $user->name,   // CamelCase vs snake_case
            'created' => $user->created_at->format('Y-m-d'),  // Different date format
        ];
    }
}
```

### 2. Resource Optimization

```php
// Good: Optimized includes
class PostTransformer extends BaseTransformer
{
    public function includeComments(Post $post)
    {
        // Load only what's needed
        $comments = $post->comments()
            ->with('author:id,name,avatar')
            ->where('status', 'approved')
            ->latest()
            ->limit(5)
            ->get();
            
        return $this->collection($comments, new CommentTransformer());
    }
}
```

### 3. Security in Transformers

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        // Only show sensitive data to authorized users
        if (auth()->user()?->can('viewSensitive', $user)) {
            $data['phone'] = $user->phone;
            $data['address'] = $user->address;
        }

        return $data;
    }
}
```

### 4. Version-aware Transformers

```php
class V1UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        // Legacy API format
        return $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}

class V2UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        // New API format with additional fields
        return $this->encodeHashIds([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'username' => $user->username,
            'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ]);
    }
}
```

## Advanced Use Cases

### Multi-format Responses

```php
class FlexibleUserTransformer extends BaseTransformer
{
    protected string $format;

    public function __construct(string $format = 'full')
    {
        parent::__construct();
        $this->format = $format;
    }

    public function transform(User $user): array
    {
        switch ($this->format) {
            case 'minimal':
                return $this->encodeHashIds([
                    'id' => $user->id,
                    'name' => $user->name,
                ]);

            case 'summary':
                return $this->encodeHashIds([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ]);

            default: // full
                return $this->encodeHashIds([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'status' => $user->status,
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                ]);
        }
    }
}

// Usage in presenter
class FlexibleUserPresenter extends FractalPresenter
{
    public function __construct(string $format = 'full')
    {
        parent::__construct(app(\League\Fractal\Manager::class));
        $this->setTransformer(new FlexibleUserTransformer($format));
    }
}
```

### Metadata Enhancement

```php
class EnhancedUserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString(),
        ]);

        // Add computed fields
        $data['display_name'] = $user->display_name ?? $user->name;
        $data['initials'] = $this->getInitials($user->name);
        $data['member_since'] = $user->created_at->diffForHumans();
        $data['is_online'] = $user->last_seen_at?->gt(now()->subMinutes(5)) ?? false;

        return $data;
    }

    private function getInitials(string $name): string
    {
        return collect(explode(' ', $name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }
}
```

## Next Steps

- [Testing Guide](testing.md) - Test your presenters and transformers
- [Caching Strategy](caching.md) - Cache transformed data efficiently
- [HashId Integration](hashids.md) - Secure ID handling in transformers
EOF

echo "📝 Creating Testing Guide..."

cat > docs/testing.md << 'EOF'
# Testing Guide

Learn how to effectively test your repositories, criteria, presenters, and HashId functionality.

## Testing Setup

### Base Test Configuration

```php
<?php

namespace Tests;

use Apiato\Repository\Providers\RepositoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            RepositoryServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Database configuration
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Repository configuration
        $app['config']->set('repository.cache.enabled', false);
        $app['config']->set('repository.hashid.enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup
        $this->artisan('migrate');
    }
}
```

### Factory Setup

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->unique()->userName(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

## Repository Testing

### Basic Repository Tests

```php
<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_can_create_user(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ];

        $user = $this->repository->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_can_find_user_by_id(): void
    {
        $user = User::factory()->create();

        $found = $this->repository->find($user->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals($user->name, $found->name);
    }

    public function test_returns_null_when_user_not_found(): void
    {
        $found = $this->repository->find(999);

        $this->assertNull($found);
    }

    public function test_find_or_fail_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findOrFail(999);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create();
        $newData = ['name' => 'Updated Name'];

        $updated = $this->repository->update($newData, $user->id);

        $this->assertEquals($newData['name'], $updated->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newData['name'],
        ]);
    }

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->delete($user->id);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_can_paginate_users(): void
    {
        User::factory()->count(25)->create();

        $results = $this->repository->paginate(10);

        $this->assertEquals(10, $results->count());
        $this->assertEquals(25, $results->total());
        $this->assertEquals(3, $results->lastPage());
    }

    public function test_can_find_by_field(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $results = $this->repository->findByField('status', 'active');

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->id);
    }

    public function test_can_find_where_in(): void
    {
        $users = User::factory()->count(3)->create();
        $ids = $users->pluck('id')->toArray();

        $results = $this->repository->findWhereIn('id', $ids);

        $this->assertCount(3, $results);
        $this->assertEquals($ids, $results->pluck('id')->sort()->values()->toArray());
    }

    public function test_can_find_where_between(): void
    {
        $start = now()->subDays(5);
        $end = now()->subDays(1);
        
        // Create users outside the range
        User::factory()->create(['created_at' => now()->subDays(10)]);
        User::factory()->create(['created_at' => now()]);
        
        // Create users within the range
        $userInRange = User::factory()->create(['created_at' => now()->subDays(3)]);

        $results = $this->repository->findWhereBetween('created_at', [$start, $end]);

        $this->assertCount(1, $results);
        $this->assertEquals($userInRange->id, $results->first()->id);
    }
}
```

### Repository with Relationships

```php
class UserRepositoryRelationshipTest extends TestCase
{
    public function test_can_find_users_with_posts(): void
    {
        $userWithPosts = User::factory()->hasPosts(3)->create();
        $userWithoutPosts = User::factory()->create();

        $results = $this->repository->query()
            ->whereHas('posts')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($userWithPosts->id, $results->first()->id);
    }

    public function test_can_eager_load_relationships(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(2)->create();

        $found = $this->repository->query()
            ->with(['profile', 'posts'])
            ->find($user->id);

        $this->assertTrue($found->relationLoaded('profile'));
        $this->assertTrue($found->relationLoaded('posts'));
        $this->assertCount(2, $found->posts);
    }
}
```

## HashId Repository Testing

### HashId Functionality Tests

```php
<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class HashIdRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_can_encode_hash_id(): void
    {
        $id = 123;
        $hashId = $this->repository->encodeHashId($id);

        $this->assertIsString($hashId);
        $this->assertNotEquals($id, $hashId);
        $this->assertGreaterThan(0, strlen($hashId));
    }

    public function test_can_decode_hash_id(): void
    {
        $id = 123;
        $hashId = $this->repository->encodeHashId($id);
        $decodedId = $this->repository->decodeHashId($hashId);

        $this->assertEquals($id, $decodedId);
    }

    public function test_decode_returns_null_for_invalid_hash_id(): void
    {
        $result = $this->repository->decodeHashId('invalid');

        $this->assertNull($result);
    }

    public function test_can_find_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $found = $this->repository->findByHashId($hashId);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_by_hash_id_returns_null_for_invalid_id(): void
    {
        $found = $this->repository->findByHashId('invalid');

        $this->assertNull($found);
    }

    public function test_can_find_by_hash_id_or_fail(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $found = $this->repository->findByHashIdOrFail($hashId);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_by_hash_id_or_fail_throws_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findByHashIdOrFail('invalid');
    }

    public function test_can_update_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);
        $newData = ['name' => 'Updated Name'];

        $updated = $this->repository->updateByHashId($newData, $hashId);

        $this->assertEquals($newData['name'], $updated->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newData['name'],
        ]);
    }

    public function test_can_delete_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $result = $this->repository->deleteByHashId($hashId);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_looks_like_hash_id_detection(): void
    {
        $this->assertTrue($this->repository->looksLikeHashId('abc123'));
        $this->assertTrue($this->repository->looksLikeHashId('XyZ789'));
        $this->assertFalse($this->repository->looksLikeHashId('123'));
        $this->assertFalse($this->repository->looksLikeHashId('abc'));
        $this->assertFalse($this->repository->looksLikeHashId(''));
    }
}
```

## Criteria Testing

### Basic Criteria Tests

```php
<?php

namespace Tests\Unit\Criteria;

use App\Criteria\ActiveUsersCriteria;
use App\Models\User;
use App\Repositories\UserRepository;
use Tests\TestCase;

class ActiveUsersCriteriaTest extends TestCase
{
    public function test_applies_active_users_filter(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);
        User::factory()->create(['status' => 'banned']);

        $repository = app(UserRepository::class);
        $criteria = new ActiveUsersCriteria();

        $results = $repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
    }

    public function test_criteria_can_be_chained(): void
    {
        User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        User::factory()->create(['status' => 'active', 'email_verified_at' => null]);
        User::factory()->create(['status' => 'inactive', 'email_verified_at' => now()]);

        $repository = app(UserRepository::class);

        $results = $repository
            ->pushCriteria(new ActiveUsersCriteria())
            ->pushCriteria(new VerifiedUsersCriteria())
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
        $this->assertNotNull($results->first()->email_verified_at);
    }

    public function test_can_skip_criteria(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $repository = app(UserRepository::class);

        $results = $repository
            ->pushCriteria(new ActiveUsersCriteria())
            ->skipCriteria()
            ->all();

        $this->assertCount(2, $results);
    }

    public function test_can_clear_criteria(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $repository = app(UserRepository::class);

        $results = $repository
            ->pushCriteria(new ActiveUsersCriteria())
            ->clearCriteria()
            ->all();

        $this->assertCount(2, $results);
    }
}
```

### Request Criteria Tests

```php
<?php

namespace Tests\Unit\Criteria;

use Apiato\Repository\Criteria\RequestCriteria;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequestCriteriaTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_applies_search_criteria(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $request = Request::create('/', 'GET', ['search' => 'name:John']);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);
    }

    public function test_applies_filter_criteria(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $request = Request::create('/', 'GET', ['filter' => 'status:active']);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
    }

    public function test_applies_order_by_criteria(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        $user3 = User::factory()->create(['name' => 'Charlie']);

        $request = Request::create('/', 'GET', [
            'orderBy' => 'name',
            'sortedBy' => 'asc'
        ]);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $results->pluck('name')->toArray());
    }

    public function test_applies_includes(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(2)->create();

        $request = Request::create('/', 'GET', ['include' => 'profile,posts']);
        $criteria = new RequestCriteria($request);

        $result = $this->repository
            ->pushCriteria($criteria)
            ->find($user->id);

        $this->assertTrue($result->relationLoaded('profile'));
        $this->assertTrue($result->relationLoaded('posts'));
    }

    public function test_handles_hash_id_search(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $request = Request::create('/', 'GET', ['search' => "id:{$hashId}"]);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->id);
    }
}
```

## Caching Tests

### Cache Functionality Tests

```php
<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheableRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable caching for tests
        config(['repository.cache.enabled' => true]);
        
        $this->repository = app(UserRepository::class);
    }

    public function test_repository_caches_results(): void
    {
        $user = User::factory()->create();
        
        // Mock cache expectations
        Cache::shouldReceive('tags')
            ->with(['users'])
            ->twice()
            ->andReturnSelf();
            
        Cache::shouldReceive('remember')
            ->twice()
            ->andReturn($user);

        // First call should hit cache
        $result1 = $this->repository->find($user->id);
        
        // Second call should also hit cache
        $result2 = $this->repository->find($user->id);

        $this->assertEquals($user->id, $result1->id);
        $this->assertEquals($user->id, $result2->id);
    }

    public function test_cache_is_cleared_on_write_operations(): void
    {
        Cache::shouldReceive('tags')
            ->with(['users'])
            ->andReturnSelf();
            
        Cache::shouldReceive('flush')
            ->once();

        $this->repository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_can_skip_cache(): void
    {
        $user = User::factory()->create();

        // When skipping cache, should not interact with cache
        Cache::shouldNotReceive('tags');
        Cache::shouldNotReceive('remember');

        $result = $this->repository
            ->skipCache()
            ->find($user->id);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_can_set_custom_cache_minutes(): void
    {
        $user = User::factory()->create();

        Cache::shouldReceive('tags')
            ->with(['users'])
            ->andReturnSelf();
            
        Cache::shouldReceive('remember')
            ->with(anything(), 120, anything()) // 120 minutes
            ->andReturn($user);

        $this->repository
            ->cacheMinutes(120)
            ->find($user->id);
    }

    public function test_generates_unique_cache_keys(): void
    {
        $key1 = $this->repository->getCacheKey('find', [1]);
        $key2 = $this->repository->getCacheKey('find', [2]);
        $key3 = $this->repository->getCacheKey('all', []);

        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertNotEquals($key2, $key3);
    }
}
```

## Presenter Testing

### Transformer Tests

```php
<?php

namespace Tests\Unit\Transformers;

use App\Models\User;
use App\Transformers\UserTransformer;
use Tests\TestCase;

class UserTransformerTest extends TestCase
{
    protected UserTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new UserTransformer();
    }

    public function test_transforms_user_data_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
        ]);

        $result = $this->transformer->transform($user);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);

        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals('johndoe', $result['username']);
    }

    public function test_encodes_hash_ids(): void
    {
        $user = User::factory()->create();
        $result = $this->transformer->transform($user);

        $this->assertNotEquals($user->id, $result['id']);
        $this->assertIsString($result['id']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result['id']);
    }

    public function test_formats_dates_correctly(): void
    {
        $user = User::factory()->create();
        $result = $this->transformer->transform($user);

        $this->assertStringContainsString('T', $result['created_at']);
        $this->assertStringContainsString('Z', $result['created_at']);
        
        // Verify it's a valid ISO 8601 date
        $this->assertNotFalse(\DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $result['created_at']));
    }
}
```

### Presenter Integration Tests

```php
<?php

namespace Tests\Unit\Presenters;

use App\Models\User;
use App\Presenters\UserPresenter;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Manager;
use Tests\TestCase;

class UserPresenterTest extends TestCase
{
    protected UserPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new UserPresenter();
    }

    public function test_presents_single_user(): void
    {
        $user = User::factory()->create();
        
        $result = $this->presenter->present($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('id', $result['data']);
        $this->assertArrayHasKey('name', $result['data']);
    }

    public function test_presents_user_collection(): void
    {
        $users = User::factory()->count(3)->create();
        
        $result = $this->presenter->present($users);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(3, $result['data']);
        
        foreach ($result['data'] as $userData) {
            $this->assertArrayHasKey('id', $userData);
            $this->assertArrayHasKey('name', $userData);
        }
    }

    public function test_presents_paginated_collection(): void
    {
        User::factory()->count(25)->create();
        
        $paginator = User::paginate(10);
        $result = $this->presenter->present($paginator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('pagination', $result['meta']);
        
        $pagination = $result['meta']['pagination'];
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(3, $pagination['last_page']);
    }
}
```

## Integration Tests

### Full API Integration Tests

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_user_list_with_filters(): void
    {
        User::factory()->create(['status' => 'active', 'name' => 'John Doe']);
        User::factory()->create(['status' => 'inactive', 'name' => 'Jane Smith']);
        User::factory()->create(['status' => 'active', 'name' => 'Bob Johnson']);

        $response = $this->getJson('/api/users?search=status:active&orderBy=name');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        
        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Bob Johnson', 'John Doe'], $names);
    }

    public function test_can_get_user_with_includes(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(2)->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);

        $response = $this->getJson("/api/users/{$hashId}?include=profile,posts");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'profile' => ['data'],
                'posts' => ['data' => [['id', 'title']]],
            ]
        ]);
    }

    public function test_can_create_user_via_api(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'created_at']
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_can_update_user_via_api(): void
    {
        $user = User::factory()->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/users/{$hashId}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => ['name' => 'Updated Name']
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_user_via_api(): void
    {
        $user = User::factory()->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);

        $response = $this->deleteJson("/api/users/{$hashId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_returns_404_for_invalid_hash_id(): void
    {
        $response = $this->getJson('/api/users/invalid-hash-id');

        $response->assertStatus(404);
    }
}
```

## Performance Testing

### Database Query Testing

```php
<?php

namespace Tests\Performance;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepositoryPerformanceTest extends TestCase
{
    public function test_eager_loading_reduces_queries(): void
    {
        // Create test data
        $users = User::factory()->count(5)->hasProfile()->hasPosts(3)->create();

        DB::enableQueryLog();

        // Without eager loading - should cause N+1 problem
        $repository = app(UserRepository::class);
        $results = $repository->all();
        
        foreach ($results as $user) {
            $user->profile; // This will trigger additional queries
            $user->posts;   // This will trigger additional queries
        }

        $queriesWithoutEagerLoading = count(DB::getQueryLog());
        DB::flushQueryLog();

        // With eager loading
        $results = $repository->query()->with(['profile', 'posts'])->get();
        
        foreach ($results as $user) {
            $user->profile; // No additional query
            $user->posts;   // No additional query
        }

        $queriesWithEagerLoading = count(DB::getQueryLog());

        $this->assertLessThan($queriesWithoutEagerLoading, $queriesWithEagerLoading);
    }

    public function test_pagination_performance(): void
    {
        User::factory()->count(1000)->create();

        $start = microtime(true);
        
        $repository = app(UserRepository::class);
        $results = $repository->paginate(50);

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration); // Should complete in under 1 second
        $this->assertEquals(50, $results->count());
        $this->assertEquals(1000, $results->total());
    }

    public function test_cache_improves_performance(): void
    {
        config(['repository.cache.enabled' => true]);
        
        User::factory()->count(100)->create();
        $repository = app(UserRepository::class);

        // First call - hits database
        $start = microtime(true);
        $results1 = $repository->all();
        $firstCallDuration = microtime(true) - $start;

        // Second call - hits cache
        $start = microtime(true);
        $results2 = $repository->all();
        $secondCallDuration = microtime(true) - $start;

        $this->assertLessThan($firstCallDuration, $secondCallDuration);
        $this->assertEquals($results1->count(), $results2->count());
    }
}
```

## Test Data Builders

### Advanced Factory Usage

```php
<?php

namespace Tests\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserBuilder
{
    private array $attributes = [];
    private array $relationships = [];

    public static function create(): self
    {
        return new self();
    }

    public function active(): self
    {
        $this->attributes['status'] = 'active';
        return $this;
    }

    public function inactive(): self
    {
        $this->attributes['status'] = 'inactive';
        return $this;
    }

    public function verified(): self
    {
        $this->attributes['email_verified_at'] = now();
        return $this;
    }

    public function withProfile(array $profileData = []): self
    {
        $this->relationships['profile'] = $profileData;
        return $this;
    }

    public function withPosts(int $count = 3, array $postData = []): self
    {
        $this->relationships['posts'] = ['count' => $count, 'data' => $postData];
        return $this;
    }

    public function admin(): self
    {
        $this->relationships['role'] = ['name' => 'admin'];
        return $this;
    }

    public function build(): User
    {
        $user = User::factory()->create($this->attributes);

        foreach ($this->relationships as $relation => $data) {
            switch ($relation) {
                case 'profile':
                    $user->profile()->create($data);
                    break;
                    
                case 'posts':
                    Post::factory()->count($data['count'])->create(
                        array_merge(['user_id' => $user->id], $data['data'])
                    );
                    break;
                    
                case 'role':
                    $role = Role::firstOrCreate(['name' => $data['name']]);
                    $user->roles()->attach($role);
                    break;
            }
        }

        return $user->fresh();
    }

    public function buildMany(int $count): Collection
    {
        return collect(range(1, $count))->map(fn() => $this->build());
    }
}

// Usage in tests
class ExampleTest extends TestCase
{
    public function test_admin_users_can_access_dashboard(): void
    {
        $admin = UserBuilder::create()
            ->active()
            ->verified()
            ->admin()
            ->withProfile(['department' => 'IT'])
            ->build();

        $this->actingAs($admin);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertStatus(200);
    }
}
```

## Continuous Integration

### GitHub Actions Test Configuration

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.1, 8.2, 8.3]
        laravel-version: [11.x, 12.x]
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, dom, fileinfo, sqlite3
        coverage: xdebug
    
    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-
    
    - name: Install dependencies
      run: |
        composer require "illuminate/framework:${{ matrix.laravel-version }}" --no-interaction --no-update
        composer install --prefer-dist --no-interaction
    
    - name: Create SQLite database
      run: |
        mkdir -p database
        touch database/database.sqlite
    
    - name: Copy environment file
      run: cp .env.testing .env
    
    - name: Generate application key
      run: php artisan key:generate
    
    - name: Run tests
      run: vendor/bin/phpunit --coverage-clover coverage.xml
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        files: ./coverage.xml
        fail_ci_if_error: true
```

## Best Practices

### 1. Test Organization

```php
// Good: Organized test structure
tests/
├── Unit/
│   ├── Repositories/
│   ├── Criteria/
│   ├── Transformers/
│   └── Presenters/
├── Feature/
│   ├── Api/
│   └── Web/
├── Integration/
│   └── FullStack/
└── Performance/
    └── Benchmarks/
```

### 2. Test Naming

```php
// Good: Descriptive test names
public function test_can_find_active_users_with_posts(): void
public function test_throws_exception_when_hash_id_is_invalid(): void
public function test_caches_repository_results_for_configured_duration(): void

// Bad: Vague test names
public function test_find(): void
public function test_hash_id(): void
public function test_cache(): void
```

### 3. Assertion Quality

```php
// Good: Specific assertions
$this->assertInstanceOf(User::class, $user);
$this->assertEquals('active', $user->status);
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertJsonStructure(['data' => ['id', 'name', 'email']]);

// Bad: Generic assertions
$this->assertTrue($user instanceof User);
$this->assertTrue($user->status == 'active');
$this->assertTrue(User::where('email', 'test@example.com')->exists());
```

### 4. Data Cleanup

```php
public function tearDown(): void
{
    // Clear any singleton instances
    app()->forgetInstance(UserRepository::class);
    
    // Clear cache
    Cache::flush();
    
    parent::tearDown();
}
```

### 5. Mock Usage

```php
// Good: Mock external dependencies
public function test_sends_notification_on_user_creation(): void
{
    $this->mock(NotificationService::class)
         ->shouldReceive('send')
         ->once()
         ->with(Mockery::type(User::class));

    $this->repository->create(['name' => 'Test', 'email' => 'test@example.com']);
}

// Bad: Testing external services
public function test_actually_sends_email(): void
{
    Mail::fake(); // This is better, but still testing external behavior
    
    $this->repository->create(['name' => 'Test', 'email' => 'test@example.com']);
    
    Mail::assertSent(WelcomeEmail::class);
}
```

## Troubleshooting Tests

### Common Test Issues

**1. Memory issues with large datasets**
```php
// Use chunking for large datasets
User::factory()->count(10000)->create();

// Better: Create in chunks
collect(range(1, 100))->each(function () {
    User::factory()->count(100)->create();
});
```

**2. Database transaction issues**
```php
// Ensure proper transaction handling
public function test_rollback_on_error(): void
{
    DB::beginTransaction();
    
    try {
        $this->repository->create(['invalid' => 'data']);
        $this->fail('Should have thrown exception');
    } catch (\Exception $e) {
        DB::rollback();
        $this->assertDatabaseEmpty('users');
    }
}
```

**3. Cache interference between tests**
```php
protected function setUp(): void
{
    parent::setUp();
    Cache::flush(); // Clear cache before each test
}
```

## Next Steps

- Review [Installation Guide](installation.md) for setup
- Explore [Repository Usage](repositories.md) for implementation
- Check [Caching Strategy](caching.md) for performance optimization
- Learn [HashId Integration](hashids.md) for security features
EOF

echo ""
echo "✅ Complete Documentation Generated!"
echo ""
echo "📚 Documentation files created:"
echo "  📄 docs/installation.md - Complete installation guide"
echo "  📄 docs/repositories.md - Repository usage and examples"
echo "  📄 docs/criteria.md - Advanced criteria system"
echo "  📄 docs/caching.md - Caching strategies and optimization"
echo "  📄 docs/hashids.md - HashId integration guide"
echo "  📄 docs/presenters.md - Fractal presenters and transformers"
echo "  📄 docs/testing.md - Comprehensive testing guide"
echo ""
echo "🎯 Features covered:"
echo "  ✅ Installation & configuration"
echo "  ✅ Basic & advanced repository usage"
echo "  ✅ Criteria system with RequestCriteria"
echo "  ✅ Intelligent caching with tags"
echo "  ✅ HashId security & API usage"
echo "  ✅ Fractal presenters & transformers"
echo "  ✅ Complete testing strategies"
echo ""
echo "📋 Next steps:"
echo "1. Review documentation for accuracy"
echo "2. Add to your repository package"
echo "3. Update README.md links"
echo "4. Create additional examples if needed"
echo ""
echo "📖 Professional documentation ready for your Apiato Repository package!"