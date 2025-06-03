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
