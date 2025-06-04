# Repository Basics - Core Functionality

Complete guide to using Apiato Repository's core features with examples from basic to advanced usage patterns.

## 📚 Table of Contents

- [Basic Repository Setup](#-basic-repository-setup)
- [Core CRUD Operations](#-core-crud-operations)
- [Advanced Querying](#-advanced-querying)
- [Field Searchable Configuration](#-field-searchable-configuration)
- [Relationships & Eager Loading](#-relationships--eager-loading)
- [Scoping Queries](#-scoping-queries)
- [Field Visibility Control](#-field-visibility-control)
- [Repository Chaining](#-repository-chaining)
- [Performance Optimization](#-performance-optimization)

## 🏗️ Basic Repository Setup

### Simple Repository

```php
<?php

namespace App\Repositories;

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use App\Models\User;

/**
 * Class UserRepository
 */
class UserRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
}
```

### Repository with Enhanced Features

```php
<?php

namespace App\Repositories;

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use App\Models\User;
use App\Presenters\UserPresenter;

class UserRepository extends BaseRepository
{
    /**
     * Specify fields that are searchable
     */
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => 'in',
        'created_at' => 'between',
        'role_id' => '=',           // HashId support automatic
    ];

    /**
     * Specify Model class name
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Specify Presenter class name (optional)
     */
    public function presenter()
    {
        return UserPresenter::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * Custom method example
     */
    public function findActiveUsers()
    {
        return $this->findWhere(['status' => 'active']);
    }

    /**
     * Find users by role (HashId supported automatically)
     */
    public function findByRole($roleId)
    {
        return $this->findWhere(['role_id' => $roleId]); // HashId decoded automatically
    }
}
```

## 🔧 Core CRUD Operations

### Basic Operations

```php
// Create repository instance
$repository = app(UserRepository::class);

// CREATE - Basic
$user = $repository->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);

// READ - Single record
$user = $repository->find(1);                    // By ID
$user = $repository->find('gY6N8');             // By HashId (automatic)
$user = $repository->first();                    // First record

// READ - Multiple records
$users = $repository->all();                     // All records
$users = $repository->paginate(15);             // Paginated (15 per page)

// UPDATE
$user = $repository->update([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
], 1); // or HashId: 'gY6N8'

// DELETE
$deleted = $repository->delete(1);               // Returns boolean
$deleted = $repository->delete('gY6N8');        // HashId support
```

### Advanced CRUD with Validation

```php
class UserRepository extends BaseRepository
{
    // Validation rules (l5-repository compatible)
    protected $rules = [
        'create' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ],
        'update' => [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,{id}',
        ],
    ];

    public function model()
    {
        return User::class;
    }

    /**
     * Create user with automatic validation
     */
    public function createValidated(array $data)
    {
        // Validation happens automatically if $rules are defined
        return $this->create($data);
    }
}

// Usage with automatic validation
try {
    $user = $repository->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password123'),
    ]);
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->errors();
}
```

## 🔍 Advanced Querying

### Find Methods

```php
// Find by field
$users = $repository->findByField('status', 'active');
$users = $repository->findByField('role_id', 'gY6N8'); // HashId automatic

// Find with where conditions
$users = $repository->findWhere([
    'status' => 'active',
    'verified' => true,
]);

// Find with where conditions and operators
$users = $repository->findWhere([
    ['age', '>', 18],
    ['status', '=', 'active'],
    ['created_at', '>=', '2024-01-01'],
]);

// Find where in
$users = $repository->findWhereIn('status', ['active', 'pending']);
$users = $repository->findWhereIn('id', ['abc123', 'def456']); // HashIds automatic

// Find where not in
$users = $repository->findWhereNotIn('status', ['banned', 'suspended']);

// Find where between
$users = $repository->findWhereBetween('age', [18, 65]);
$users = $repository->findWhereBetween('created_at', ['2024-01-01', '2024-12-31']);
```

### Complex Query Examples

```php
// Multiple conditions with relationships
$users = $repository
    ->with(['posts', 'roles'])
    ->findWhere([
        'status' => 'active',
        ['posts_count', '>', 10],
        ['created_at', '>=', now()->subDays(30)],
    ]);

// Using scope queries for complex logic
$activeUsers = $repository->scopeQuery(function($query) {
    return $query->where('status', 'active')
                 ->whereHas('posts', function($q) {
                     $q->where('published', true);
                 })
                 ->orderBy('last_login', 'desc');
})->paginate(20);

// Chaining multiple methods
$users = $repository
    ->with(['profile', 'roles'])
    ->orderBy('created_at', 'desc')
    ->findWhere(['status' => 'active']);
```

## 🔍 Field Searchable Configuration

### Basic Search Configuration

```php
class UserRepository extends BaseRepository
{
    protected $fieldSearchable = [
        // Exact match
        'email' => '=',
        'status' => '=',
        
        // Like search (partial matching)
        'name' => 'like',
        'bio' => 'like',
        
        // In array search
        'role' => 'in',
        'tags' => 'in',
        
        // Comparison operators
        'age' => '>=',
        'created_at' => 'between',
        
        // HashId fields (automatic decoding)
        'role_id' => '=',
        'department_id' => '=',
    ];
}
```

### Advanced Search Configuration

```php
class PostRepository extends BaseRepository
{
    protected $fieldSearchable = [
        // Basic fields
        'title' => 'like',
        'status' => '=',
        'published' => '=',
        
        // Relationship searches (nested)
        'user.name' => 'like',           // Search user's name
        'user.email' => '=',             // Search user's email
        'category.name' => 'like',       // Search category name
        'tags.name' => 'like',           // Search tag names
        
        // Date ranges
        'created_at' => 'between',
        'published_at' => 'between',
        
        // Numeric comparisons
        'views_count' => '>=',
        'likes_count' => '>=',
        
        // HashId relationships
        'user_id' => '=',
        'category_id' => '=',
    ];

    public function model()
    {
        return Post::class;
    }
}

// Usage examples:
// GET /api/posts?search=title:Laravel;user.name:John
// GET /api/posts?search=status:published;created_at:between:2024-01-01,2024-12-31
// GET /api/posts?search=user_id:gY6N8  (HashId decoded automatically)
```

## 🔗 Relationships & Eager Loading

### Basic Relationships

```php
// Load with single relationship
$users = $repository->with(['posts'])->all();

// Load with multiple relationships
$users = $repository->with(['posts', 'roles', 'profile'])->paginate(15);

// Nested relationships
$users = $repository->with([
    'posts.comments',
    'roles.permissions',
    'profile.avatar'
])->all();
```

### Advanced Relationship Loading

```php
// Conditional relationship loading
$users = $repository->with([
    'posts' => function($query) {
        $query->where('published', true)
              ->orderBy('created_at', 'desc')
              ->limit(5);
    },
    'roles' => function($query) {
        $query->where('active', true);
    }
])->findWhere(['status' => 'active']);

// Using whereHas for filtering
$usersWithPosts = $repository->whereHas('posts', function($query) {
    $query->where('published', true)
          ->where('created_at', '>=', now()->subDays(30));
})->paginate(15);

// Using has for existence checks
$usersWithAnyPosts = $repository->has('posts')->all();
```

### Relationship Query Examples

```php
class UserRepository extends BaseRepository
{
    /**
     * Get users with recent posts
     */
    public function getUsersWithRecentPosts($days = 30)
    {
        return $this->with(['posts' => function($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days))
                  ->orderBy('created_at', 'desc');
        }])->whereHas('posts', function($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        })->paginate(15);
    }

    /**
     * Get users by role (supports HashId)
     */
    public function getUsersByRole($roleId)
    {
        return $this->with(['roles'])
                    ->whereHas('roles', function($query) use ($roleId) {
                        // HashId automatically decoded
                        $query->where('id', $roleId);
                    })->paginate(15);
    }
}
```

## 🎯 Scoping Queries

### Basic Scoping

```php
// Simple scope
$activeUsers = $repository->scopeQuery(function($query) {
    return $query->where('status', 'active');
})->all();

// Multiple scopes
$premiumActiveUsers = $repository->scopeQuery(function($query) {
    return $query->where('status', 'active')
                 ->where('subscription', 'premium')
                 ->orderBy('last_login', 'desc');
})->paginate(15);
```

### Advanced Scoping with Parameters

```php
class UserRepository extends BaseRepository
{
    /**
     * Scope to active users only
     */
    public function activeUsers()
    {
        return $this->scopeQuery(function($query) {
            return $query->where('status', 'active');
        });
    }

    /**
     * Scope to users with specific role
     */
    public function withRole($roleId)
    {
        return $this->scopeQuery(function($query) use ($roleId) {
            return $query->whereHas('roles', function($q) use ($roleId) {
                // HashId support automatic
                $q->where('id', $roleId);
            });
        });
    }

    /**
     * Scope to recent users
     */
    public function recent($days = 30)
    {
        return $this->scopeQuery(function($query) use ($days) {
            return $query->where('created_at', '>=', now()->subDays($days));
        });
    }
}

// Usage - chainable scopes
$users = $repository
    ->activeUsers()
    ->withRole('admin_role_hashid')
    ->recent(7)
    ->paginate(15);
```

### Dynamic Scoping

```php
/**
 * Apply multiple dynamic scopes
 */
public function applyFilters(array $filters)
{
    return $this->scopeQuery(function($query) use ($filters) {
        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Role filter (HashId support)
        if (isset($filters['role_id'])) {
            $query->whereHas('roles', function($q) use ($filters) {
                $q->where('id', $filters['role_id']);
            });
        }

        // Search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    });
}

// Usage
$users = $repository->applyFilters([
    'status' => 'active',
    'role_id' => 'admin_hashid',
    'date_from' => '2024-01-01',
    'search' => 'john',
])->paginate(15);
```

## 👁️ Field Visibility Control

### Basic Visibility

```php
// Hide specific fields
$users = $repository
    ->hidden(['password', 'remember_token'])
    ->all();

// Show only specific fields
$users = $repository
    ->visible(['id', 'name', 'email'])
    ->all();

// Chain with other methods
$users = $repository
    ->with(['posts'])
    ->hidden(['password', 'email_verified_at'])
    ->paginate(15);
```

### Dynamic Visibility Based on User Role

```php
class UserRepository extends BaseRepository
{
    /**
     * Get users with role-based field visibility
     */
    public function getUsersForRole($currentUserRole)
    {
        $query = $this->with(['profile']);

        // Admin sees everything
        if ($currentUserRole === 'admin') {
            return $query->all();
        }

        // Manager sees limited fields
        if ($currentUserRole === 'manager') {
            return $query->hidden([
                'password', 
                'remember_token', 
                'email_verified_at'
            ])->all();
        }

        // Regular users see public fields only
        return $query->visible([
            'id', 
            'name', 
            'profile.avatar', 
            'created_at'
        ])->all();
    }
}
```

### Context-Aware Visibility

```php
/**
 * Repository method with context-aware visibility
 */
public function getForContext($context = 'public')
{
    $query = $this->with(['profile', 'posts']);

    switch ($context) {
        case 'admin':
            // Admin context - show everything
            return $query->all();

        case 'api':
            // API context - hide sensitive fields
            return $query->hidden([
                'password',
                'remember_token',
                'email_verified_at',
                'two_factor_secret',
            ])->all();

        case 'public':
            // Public context - minimal fields
            return $query->visible([
                'id',
                'name',
                'profile.avatar',
                'profile.bio',
                'posts.title',
            ])->all();

        default:
            return $query->hidden(['password', 'remember_token'])->all();
    }
}

// Usage
$adminView = $repository->getForContext('admin');
$apiView = $repository->getForContext('api');
$publicView = $repository->getForContext('public');
```

## 🔗 Repository Chaining

### Method Chaining Examples

```php
// Basic chaining
$users = $repository
    ->with(['posts', 'roles'])
    ->orderBy('created_at', 'desc')
    ->findWhere(['status' => 'active']);

// Complex chaining
$premiumUsers = $repository
    ->with([
        'subscription',
        'posts' => function($query) {
            $query->where('published', true)->latest();
        }
    ])
    ->whereHas('subscription', function($query) {
        $query->where('type', 'premium')
              ->where('expires_at', '>', now());
    })
    ->hidden(['password', 'remember_token'])
    ->orderBy('last_login', 'desc')
    ->paginate(20);
```

### Building Dynamic Queries

```php
class UserRepository extends BaseRepository
{
    /**
     * Build a dynamic query based on parameters
     */
    public function buildQuery(array $params = [])
    {
        $query = $this;

        // Add relationships if requested
        if (!empty($params['include'])) {
            $relationships = explode(',', $params['include']);
            $query = $query->with($relationships);
        }

        // Add search if provided
        if (!empty($params['search'])) {
            $query = $query->scopeQuery(function($q) use ($params) {
                return $q->where('name', 'like', "%{$params['search']}%")
                        ->orWhere('email', 'like', "%{$params['search']}%");
            });
        }

        // Add status filter
        if (!empty($params['status'])) {
            $query = $query->scopeQuery(function($q) use ($params) {
                return $q->where('status', $params['status']);
            });
        }

        // Add ordering
        if (!empty($params['sort'])) {
            $direction = $params['order'] ?? 'asc';
            $query = $query->orderBy($params['sort'], $direction);
        }

        // Add field visibility
        if (!empty($params['fields'])) {
            $fields = explode(',', $params['fields']);
            $query = $query->visible($fields);
        }

        return $query;
    }
}

// Usage
$users = $repository->buildQuery([
    'include' => 'posts,roles',
    'search' => 'john',
    'status' => 'active',
    'sort' => 'created_at',
    'order' => 'desc',
    'fields' => 'id,name,email,created_at',
])->paginate(15);
```

## ⚡ Performance Optimization

### Repository-Level Caching

```php
class UserRepository extends BaseRepository
{
    // Cache results for 60 minutes
    protected $cacheMinutes = 60;

    /**
     * Cached method example
     */
    public function getActiveUsers()
    {
        // This will be cached automatically
        return $this->findWhere(['status' => 'active']);
    }

    /**
     * Force cache skip for real-time data
     */
    public function getRealtimeUsers()
    {
        return $this->skipCache()->all();
    }
}
```

### Query Optimization

```php
/**
 * Optimized queries for large datasets
 */
public function getOptimizedListing($page = 1, $limit = 15)
{
    return $this
        ->with(['profile:id,user_id,avatar'])  // Select specific columns
        ->visible(['id', 'name', 'email', 'created_at'])  // Limit response
        ->orderBy('id', 'desc')  // Use indexed column for ordering
        ->paginate($limit);
}

/**
 * Batch operations for performance
 */
public function createMultiple(array $usersData)
{
    // Disable events for batch operations
    $this->model->unguard();
    
    $users = collect($usersData)->map(function($data) {
        return $this->model->newInstance($data);
    });
    
    // Bulk insert for better performance
    $this->model->insert($users->toArray());
    
    $this->model->reguard();
    
    return $users;
}
```

### Memory Optimization

```php
/**
 * Memory-efficient iteration for large datasets
 */
public function processLargeDataset(\Closure $callback)
{
    $this->model->chunk(1000, function($users) use ($callback) {
        foreach ($users as $user) {
            $callback($user);
        }
    });
}

// Usage
$repository->processLargeDataset(function($user) {
    // Process each user without loading all into memory
    $this->sendNotification($user);
});
```

---

**Next:** Learn about **[Criteria System](criteria.md)** for advanced filtering and searching capabilities.