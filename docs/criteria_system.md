# Criteria System - Advanced Filtering

Complete guide to using Apiato Repository's powerful criteria system for dynamic filtering, searching, and query building with enhanced performance and HashId support.

## 📚 Table of Contents

- [Understanding Criteria](#-understanding-criteria)
- [RequestCriteria (Enhanced)](#-requestcriteria-enhanced)
- [Custom Criteria](#-custom-criteria)
- [Criteria Stacking](#-criteria-stacking)
- [Advanced Search Patterns](#-advanced-search-patterns)
- [API Integration](#-api-integration)
- [Performance Optimization](#-performance-optimization)

## 🧠 Understanding Criteria

Criteria are reusable query filters that can be applied to repositories to modify queries dynamically. They provide a clean, testable way to build complex queries.

### Basic Criteria Concept

```php
// Without Criteria (hard to reuse)
$activeUsers = User::where('status', 'active')
                  ->where('verified', true)
                  ->get();

// With Criteria (reusable, testable)
$activeUsers = $repository
    ->pushCriteria(new ActiveUsersCriteria())
    ->pushCriteria(new VerifiedUsersCriteria())
    ->all();
```

### Criteria Interface

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

class ExampleCriteria implements CriteriaInterface
{
    /**
     * Apply criteria in query repository
     */
    public function apply($model, RepositoryInterface $repository)
    {
        // Modify the query here
        return $model->where('status', 'active');
    }
}
```

## 🔍 RequestCriteria (Enhanced)

RequestCriteria automatically applies filters, search, sorting, and relationships based on HTTP request parameters. It's enhanced in Apiato Repository with HashId support and performance improvements.

### Basic Setup

```php
class UserRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => '=',
        'role_id' => '=',        // HashId support automatic
        'created_at' => 'between',
    ];

    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
}
```

### Search Parameters

#### Basic Search
```bash
# Search across multiple fields
GET /api/users?search=john

# Field-specific search
GET /api/users?search=name:john

# Multiple field search
GET /api/users?search=name:john;email:john@example.com

# HashId search (automatic decoding)
GET /api/users?search=role_id:gY6N8
```

#### Advanced Search with Operators
```bash
# Like search (partial matching)
GET /api/users?search=name:like:john

# Exact search
GET /api/users?search=email:=:john@example.com

# In search (multiple values)
GET /api/users?search=status:in:active,pending

# Between search (ranges)
GET /api/users?search=created_at:between:2024-01-01,2024-12-31

# Comparison operators
GET /api/users?search=age:>=:18
GET /api/users?search=posts_count:>:10
```

#### Relationship Search
```bash
# Search in related models
GET /api/users?search=posts.title:like:laravel

# Multiple relationship searches
GET /api/users?search=posts.title:like:laravel;profile.bio:like:developer

# HashId in relationships
GET /api/users?search=department.id:gY6N8
```

### Filter Parameters

```bash
# Simple filters
GET /api/users?filter=status:active

# Multiple filters
GET /api/users?filter=status:active;verified:true

# HashId filters (automatic decoding)
GET /api/users?filter=role_id:gY6N8;department_id:abc123
```

### Sorting Parameters

```bash
# Simple sorting
GET /api/users?orderBy=created_at&sortedBy=desc

# Multiple column sorting
GET /api/users?orderBy=status,created_at&sortedBy=asc,desc
```

### Relationship Loading

```bash
# Load single relationship
GET /api/users?with=posts

# Load multiple relationships
GET /api/users?with=posts,roles,profile

# Load nested relationships
GET /api/users?with=posts.comments,roles.permissions
```

### Complete RequestCriteria Example

```bash
# Complex API call with all features
GET /api/users?search=name:like:john;role_id:gY6N8&filter=status:active&orderBy=created_at&sortedBy=desc&with=posts,profile
```

## 🏗️ Custom Criteria

### Simple Custom Criteria

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Show only active users
 */
class ActiveUsersCriteria implements CriteriaInterface
{
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('status', 'active');
    }
}
```

### Parameterized Criteria

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Filter users by role (supports HashId)
 */
class UserByRoleCriteria implements CriteriaInterface
{
    protected $roleId;

    public function __construct($roleId)
    {
        $this->roleId = $roleId;
    }

    public function apply($model, RepositoryInterface $repository)
    {
        // HashId is automatically processed by the repository
        return $model->whereHas('roles', function($query) {
            $query->where('id', $this->roleId);
        });
    }
}

// Usage
$admins = $repository
    ->pushCriteria(new UserByRoleCriteria('admin_role_hashid'))
    ->all();
```

### Advanced Custom Criteria

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;
use Carbon\Carbon;

/**
 * Advanced user filtering with multiple conditions
 */
class AdvancedUserFilterCriteria implements CriteriaInterface
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function apply($model, RepositoryInterface $repository)
    {
        // Status filter
        if (isset($this->filters['status'])) {
            $model = $model->where('status', $this->filters['status']);
        }

        // Registration date range
        if (isset($this->filters['registered_after'])) {
            $model = $model->where('created_at', '>=', 
                Carbon::parse($this->filters['registered_after'])
            );
        }

        if (isset($this->filters['registered_before'])) {
            $model = $model->where('created_at', '<=', 
                Carbon::parse($this->filters['registered_before'])
            );
        }

        // Role filter (HashId supported)
        if (isset($this->filters['role_id'])) {
            $model = $model->whereHas('roles', function($query) {
                $query->where('id', $this->filters['role_id']);
            });
        }

        // Activity filter
        if (isset($this->filters['min_posts'])) {
            $model = $model->whereHas('posts', function($query) {
                $query->selectRaw('count(*)')
                      ->havingRaw('count(*) >= ?', [$this->filters['min_posts']]);
            });
        }

        // Search filter
        if (isset($this->filters['search'])) {
            $search = $this->filters['search'];
            $model = $model->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('profile', function($q) use ($search) {
                          $q->where('bio', 'like', "%{$search}%");
                      });
            });
        }

        return $model;
    }
}

// Usage
$users = $repository->pushCriteria(
    new AdvancedUserFilterCriteria([
        'status' => 'active',
        'role_id' => 'author_role_hashid',
        'registered_after' => '2024-01-01',
        'min_posts' => 5,
        'search' => 'laravel developer',
    ])
)->paginate(15);
```

### Dynamic Criteria Builder

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Dynamic criteria that builds queries from array configuration
 */
class DynamicFilterCriteria implements CriteriaInterface
{
    protected $conditions;

    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    public function apply($model, RepositoryInterface $repository)
    {
        foreach ($this->conditions as $condition) {
            $model = $this->applyCondition($model, $condition, $repository);
        }

        return $model;
    }

    protected function applyCondition($model, $condition, $repository)
    {
        $field = $condition['field'];
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'];

        // Handle HashId fields automatically
        if (method_exists($repository, 'processIdValue') && 
            str_ends_with($field, '_id')) {
            $value = $repository->processIdValue($value);
        }

        switch ($operator) {
            case 'like':
                return $model->where($field, 'like', "%{$value}%");
            
            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                return $model->whereIn($field, $values);
            
            case 'between':
                $values = is_array($value) ? $value : explode(',', $value);
                return $model->whereBetween($field, $values);
            
            case 'null':
                return $model->whereNull($field);
            
            case 'not_null':
                return $model->whereNotNull($field);
            
            case 'has_relation':
                return $model->has($value);
            
            case 'where_has':
                return $model->whereHas($value['relation'], $value['callback']);
            
            default:
                return $model->where($field, $operator, $value);
        }
    }
}

// Usage
$conditions = [
    ['field' => 'status', 'operator' => '=', 'value' => 'active'],
    ['field' => 'name', 'operator' => 'like', 'value' => 'john'],
    ['field' => 'role_id', 'operator' => 'in', 'value' => ['hash1', 'hash2']],
    ['field' => 'created_at', 'operator' => 'between', 'value' => ['2024-01-01', '2024-12-31']],
];

$users = $repository
    ->pushCriteria(new DynamicFilterCriteria($conditions))
    ->paginate(15);
```

## 📚 Criteria Stacking

### Basic Stacking

```php
// Stack multiple criteria
$users = $repository
    ->pushCriteria(new ActiveUsersCriteria())
    ->pushCriteria(new VerifiedUsersCriteria())
    ->pushCriteria(new RecentUsersCriteria(30))
    ->all();
```

### Conditional Stacking

```php
class UserRepository extends BaseRepository
{
    public function getFilteredUsers(array $filters = [])
    {
        // Always apply base criteria
        $this->pushCriteria(app(RequestCriteria::class));

        // Conditionally apply additional criteria
        if (isset($filters['only_active'])) {
            $this->pushCriteria(new ActiveUsersCriteria());
        }

        if (isset($filters['role_id'])) {
            $this->pushCriteria(new UserByRoleCriteria($filters['role_id']));
        }

        if (isset($filters['recent_days'])) {
            $this->pushCriteria(new RecentUsersCriteria($filters['recent_days']));
        }

        if (isset($filters['has_avatar'])) {
            $this->pushCriteria(new UsersWithAvatarCriteria());
        }

        return $this->paginate();
    }
}

// Usage
$users = $repository->getFilteredUsers([
    'only_active' => true,
    'role_id' => 'admin_hashid',
    'recent_days' => 7,
    'has_avatar' => true,
]);
```

### Criteria Management

```php
// Get all applied criteria
$criteria = $repository->getCriteria();

// Skip criteria temporarily
$allUsers = $repository->skipCriteria()->all();

// Remove specific criteria
$repository->popCriteria(ActiveUsersCriteria::class);

// Clear all criteria
$repository->clearCriteria();

// Re-apply criteria after clearing
$repository->pushCriteria(new ActiveUsersCriteria());
```

## 🎯 Advanced Search Patterns

### Elasticsearch-Style Search

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Elasticsearch-like search functionality
 */
class ElasticsearchStyleCriteria implements CriteriaInterface
{
    protected $query;

    public function __construct(array $query)
    {
        $this->query = $query;
    }

    public function apply($model, RepositoryInterface $repository)
    {
        // Handle "must" conditions (AND)
        if (isset($this->query['must'])) {
            foreach ($this->query['must'] as $condition) {
                $model = $this->applyCondition($model, $condition, 'and');
            }
        }

        // Handle "should" conditions (OR)
        if (isset($this->query['should'])) {
            $model = $model->where(function($query) {
                foreach ($this->query['should'] as $condition) {
                    $query = $this->applyCondition($query, $condition, 'or');
                }
                return $query;
            });
        }

        // Handle "must_not" conditions (NOT)
        if (isset($this->query['must_not'])) {
            foreach ($this->query['must_not'] as $condition) {
                $model = $this->applyCondition($model, $condition, 'and_not');
            }
        }

        return $model;
    }

    protected function applyCondition($query, $condition, $type = 'and')
    {
        $method = $type === 'or' ? 'orWhere' : 'where';
        $method = $type === 'and_not' ? 'whereNot' : $method;

        if (isset($condition['term'])) {
            // Exact match
            $field = key($condition['term']);
            $value = $condition['term'][$field];
            return $query->$method($field, $value);
        }

        if (isset($condition['match'])) {
            // Like search
            $field = key($condition['match']);
            $value = $condition['match'][$field];
            return $query->$method($field, 'like', "%{$value}%");
        }

        if (isset($condition['range'])) {
            // Range search
            $field = key($condition['range']);
            $range = $condition['range'][$field];
            
            if (isset($range['gte'])) {
                $query = $query->$method($field, '>=', $range['gte']);
            }
            if (isset($range['lte'])) {
                $query = $query->$method($field, '<=', $range['lte']);
            }
            return $query;
        }

        return $query;
    }
}

// Usage
$searchQuery = [
    'must' => [
        ['term' => ['status' => 'active']],
        ['match' => ['name' => 'john']],
    ],
    'should' => [
        ['term' => ['role_id' => 'admin_hashid']],
        ['term' => ['role_id' => 'manager_hashid']],
    ],
    'must_not' => [
        ['term' => ['banned' => true]],
    ],
];

$users = $repository
    ->pushCriteria(new ElasticsearchStyleCriteria($searchQuery))
    ->paginate(15);
```

### Full-Text Search Criteria

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Full-text search across multiple fields and relationships
 */
class FullTextSearchCriteria implements CriteriaInterface
{
    protected $searchTerm;
    protected $searchFields;

    public function __construct($searchTerm, array $searchFields = [])
    {
        $this->searchTerm = $searchTerm;
        $this->searchFields = $searchFields ?: [
            'name', 'email', 'profile.bio', 'posts.title', 'posts.content'
        ];
    }

    public function apply($model, RepositoryInterface $repository)
    {
        if (empty($this->searchTerm)) {
            return $model;
        }

        $terms = explode(' ', $this->searchTerm);

        return $model->where(function($query) use ($terms) {
            foreach ($terms as $term) {
                $query->where(function($q) use ($term) {
                    // Search in direct fields
                    foreach ($this->getDirectFields() as $field) {
                        $q->orWhere($field, 'like', "%{$term}%");
                    }

                    // Search in relationships
                    foreach ($this->getRelationshipFields() as $relation => $fields) {
                        $q->orWhereHas($relation, function($relationQuery) use ($fields, $term) {
                            foreach ($fields as $field) {
                                $relationQuery->orWhere($field, 'like', "%{$term}%");
                            }
                        });
                    }
                });
            }
        });
    }

    protected function getDirectFields()
    {
        return array_filter($this->searchFields, function($field) {
            return !str_contains($field, '.');
        });
    }

    protected function getRelationshipFields()
    {
        $relationFields = [];
        
        foreach ($this->searchFields as $field) {
            if (str_contains($field, '.')) {
                [$relation, $relationField] = explode('.', $field, 2);
                $relationFields[$relation][] = $relationField;
            }
        }

        return $relationFields;
    }
}

// Usage
$users = $repository
    ->pushCriteria(new FullTextSearchCriteria('laravel developer php'))
    ->paginate(15);
```

## 🌐 API Integration

### RESTful API Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Criteria\ActiveUsersCriteria;
use App\Criteria\UserByRoleCriteria;
use App\Criteria\FullTextSearchCriteria;

class UserController extends Controller
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * GET /api/users
     * Supports: search, filter, pagination, includes, sorting
     */
    public function index()
    {
        // RequestCriteria automatically handles query parameters
        $users = $this->repository->paginate(
            request('per_page', 15)
        );

        return response()->json($users);
    }

    /**
     * GET /api/users/search
     * Advanced search endpoint
     */
    public function search()
    {
        // Apply base criteria
        $this->repository->pushCriteria(app(RequestCriteria::class));

        // Add full-text search if term provided
        if ($searchTerm = request('q')) {
            $this->repository->pushCriteria(
                new FullTextSearchCriteria($searchTerm)
            );
        }

        // Filter by role if provided (supports HashId)
        if ($roleId = request('role_id')) {
            $this->repository->pushCriteria(
                new UserByRoleCriteria($roleId)
            );
        }

        // Only show active users by default
        if (request('include_inactive') !== 'true') {
            $this->repository->pushCriteria(new ActiveUsersCriteria());
        }

        return response()->json(
            $this->repository->paginate(request('per_page', 15))
        );
    }

    /**
     * GET /api/users/advanced-search
     * Complex search with multiple criteria
     */
    public function advancedSearch()
    {
        $filters = request()->all();

        // Build dynamic criteria based on request
        if (isset($filters['elasticsearch_query'])) {
            $this->repository->pushCriteria(
                new ElasticsearchStyleCriteria($filters['elasticsearch_query'])
            );
        }

        if (isset($filters['advanced_filters'])) {
            $this->repository->pushCriteria(
                new AdvancedUserFilterCriteria($filters['advanced_filters'])
            );
        }

        return response()->json(
            $this->repository->paginate(request('per_page', 15))
        );
    }
}
```

### API Usage Examples

```bash
# Basic search with RequestCriteria
GET /api/users?search=name:john&filter=status:active&orderBy=created_at&sortedBy=desc

# Advanced search endpoint
GET /api/users/search?q=laravel+developer&role_id=author_hashid&include_inactive=false

# Full-text search across relationships
GET /api/users/search?q=php+laravel&with=posts,profile

# Elasticsearch-style query
POST /api/users/advanced-search
{
    "elasticsearch_query": {
        "must": [
            {"term": {"status": "active"}},
            {"match": {"name": "john"}}
        ],
        "should": [
            {"term": {"role_id": "admin_hashid"}},
            {"term": {"role_id": "manager_hashid"}}
        ]
    }
}
```

## ⚡ Performance Optimization

### Cached Criteria

```php
<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Cached criteria for expensive operations
 */
class CachedPopularUsersCriteria implements CriteriaInterface
{
    protected $cacheMinutes;

    public function __construct($cacheMinutes = 60)
    {
        $this->cacheMinutes = $cacheMinutes;
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $cacheKey = 'popular_users_ids_' . md5(serialize($model->getQuery()));
        
        $popularUserIds = Cache::remember($cacheKey, $this->cacheMinutes, function() use ($model) {
            // Expensive calculation cached
            return $model->withCount(['posts', 'followers'])
                        ->havingRaw('posts_count + followers_count > 100')
                        ->pluck('id');
        });

        return $model->whereIn('id', $popularUserIds);
    }
}
```

### Database Index-Optimized Criteria

```php
<?php

namespace App\Criteria;

/**
 * Criteria optimized for database indexes
 */
class IndexOptimizedUsersCriteria implements CriteriaInterface
{
    public function apply($model, RepositoryInterface $repository)
    {
        // Use indexed columns first
        return $model->where('status', 'active')        // Indexed
                    ->where('verified', true)           // Indexed
                    ->orderBy('id', 'desc');           // Primary key ordering
    }
}
```

### Batch Processing Criteria

```php
<?php

namespace App\Criteria;

/**
 * Criteria for efficient batch processing
 */
class BatchProcessingCriteria implements CriteriaInterface
{
    protected $batchSize;
    protected $lastProcessedId;

    public function __construct($batchSize = 1000, $lastProcessedId = 0)
    {
        $this->batchSize = $batchSize;
        $this->lastProcessedId = $lastProcessedId;
    }

    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('id', '>', $this->lastProcessedId)
                    ->orderBy('id')
                    ->limit($this->batchSize);
    }
}

// Usage for processing large datasets
$lastId = 0;
do {
    $users = $repository
        ->pushCriteria(new BatchProcessingCriteria(1000, $lastId))
        ->all();
    
    if ($users->isNotEmpty()) {
        // Process batch
        foreach ($users as $user) {
            $this->processUser($user);
        }
        
        $lastId = $users->last()->id;
    }
} while ($users->count() === 1000);
```

---

**Next:** Learn about **[Presenters & Transformers](presenters.md)** for formatting your data output.