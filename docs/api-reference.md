# API Reference

Complete API reference for the Apiato Repository package.

## Repository Interface

### Core Methods

#### `all($columns = ['*'])`
Retrieve all records from the repository.

```php
$users = $this->userRepository->all();
$users = $this->userRepository->all(['id', 'name', 'email']);
```

**Parameters:**
- `$columns` (array): Columns to select

**Returns:** `Collection`

---

#### `paginate($limit = null, $columns = ['*'])`
Paginate records from the repository.

```php
$users = $this->userRepository->paginate(15);
$users = $this->userRepository->paginate(20, ['id', 'name', 'email']);
```

**Parameters:**
- `$limit` (int|null): Number of records per page
- `$columns` (array): Columns to select

**Returns:** `LengthAwarePaginator`

---

#### `find($id, $columns = ['*'])`
Find a record by ID.

```php
$user = $this->userRepository->find(1);
$user = $this->userRepository->find('abc123'); // HashId
$user = $this->userRepository->find(1, ['id', 'name', 'email']);
```

**Parameters:**
- `$id` (mixed): Record ID (numeric or HashId)
- `$columns` (array): Columns to select

**Returns:** `Model|null`

---

#### `findOrFail($id, $columns = ['*'])`
Find a record by ID or throw an exception.

```php
$user = $this->userRepository->findOrFail(1);
$user = $this->userRepository->findOrFail('abc123'); // HashId
```

**Parameters:**
- `$id` (mixed): Record ID (numeric or HashId)
- `$columns` (array): Columns to select

**Returns:** `Model`
**Throws:** `ModelNotFoundException`

---

#### `findByField($field, $value, $columns = ['*'])`
Find records by a specific field.

```php
$users = $this->userRepository->findByField('status', 'active');
$user = $this->userRepository->findByField('email', 'john@example.com');
```

**Parameters:**
- `$field` (string): Field name
- `$value` (mixed): Field value
- `$columns` (array): Columns to select

**Returns:** `Collection`

---

#### `findWhere(array $where, $columns = ['*'])`
Find records matching multiple conditions.

```php
$users = $this->userRepository->findWhere([
    'status' => 'active',
    'verified' => true
]);

$users = $this->userRepository->findWhere([
    ['created_at', '>=', '2024-01-01'],
    ['status', '!=', 'banned']
]);
```

**Parameters:**
- `$where` (array): Where conditions
- `$columns` (array): Columns to select

**Returns:** `Collection`

---

#### `create(array $attributes)`
Create a new record.

```php
$user = $this->userRepository->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password')
]);
```

**Parameters:**
- `$attributes` (array): Record attributes

**Returns:** `Model`

---

#### `update(array $attributes, $id)`
Update an existing record.

```php
$user = $this->userRepository->update([
    'name' => 'Jane Doe'
], 1);

$user = $this->userRepository->update([
    'name' => 'Jane Doe'
], 'abc123'); // HashId
```

**Parameters:**
- `$attributes` (array): Attributes to update
- `$id` (mixed): Record ID (numeric or HashId)

**Returns:** `Model`

---

#### `delete($id)`
Delete a record by ID.

```php
$deleted = $this->userRepository->delete(1);
$deleted = $this->userRepository->delete('abc123'); // HashId
```

**Parameters:**
- `$id` (mixed): Record ID (numeric or HashId)

**Returns:** `bool|int`

## HashId Methods

#### `encodeHashId(int $id)`
Encode a numeric ID to HashId.

```php
$hashId = $this->userRepository->encodeHashId(123);
// Returns: "abc123"
```

**Parameters:**
- `$id` (int): Numeric ID

**Returns:** `string`

---

#### `decodeHashId(string $hashId)`
Decode a HashId to numeric ID.

```php
$id = $this->userRepository->decodeHashId('abc123');
// Returns: 123
```

**Parameters:**
- `$hashId` (string): HashId string

**Returns:** `int|null`

---

#### `findByHashId(string $hashId, $columns = ['*'])`
Find a record by HashId.

```php
$user = $this->userRepository->findByHashId('abc123');
```

**Parameters:**
- `$hashId` (string): HashId string
- `$columns` (array): Columns to select

**Returns:** `Model|null`

---

#### `findByHashIdOrFail(string $hashId, $columns = ['*'])`
Find a record by HashId or throw an exception.

```php
$user = $this->userRepository->findByHashIdOrFail('abc123');
```

**Parameters:**
- `$hashId` (string): HashId string
- `$columns` (array): Columns to select

**Returns:** `Model`
**Throws:** `ModelNotFoundException`

## Criteria Methods

#### `pushCriteria($criteria)`
Add criteria to the repository.

```php
$this->userRepository
    ->pushCriteria(new ActiveUsersCriteria())
    ->pushCriteria(new RequestCriteria($request));
```

**Parameters:**
- `$criteria` (CriteriaInterface|string): Criteria instance or class name

**Returns:** `Repository`

---

#### `popCriteria($criteria)`
Remove criteria from the repository.

```php
$this->userRepository->popCriteria(ActiveUsersCriteria::class);
```

**Parameters:**
- `$criteria` (CriteriaInterface|string): Criteria instance or class name

**Returns:** `Repository`

---

#### `clearCriteria()`
Clear all criteria from the repository.

```php
$this->userRepository->clearCriteria();
```

**Returns:** `Repository`

---

#### `skipCriteria($status = true)`
Skip criteria application.

```php
$users = $this->userRepository
    ->skipCriteria()
    ->all();
```

**Parameters:**
- `$status` (bool): Whether to skip criteria

**Returns:** `Repository`

## Cache Methods

#### `skipCache($status = true)`
Skip cache for the next operation.

```php
$users = $this->userRepository
    ->skipCache()
    ->all();
```

**Parameters:**
- `$status` (bool): Whether to skip cache

**Returns:** `Repository`

---

#### `cacheMinutes(int $minutes)`
Set cache duration for the next operation.

```php
$users = $this->userRepository
    ->cacheMinutes(120)
    ->all();
```

**Parameters:**
- `$minutes` (int): Cache duration in minutes

**Returns:** `Repository`

---

#### `cacheKey(string $key)`
Set custom cache key for the next operation.

```php
$users = $this->userRepository
    ->cacheKey('active_users')
    ->findWhere(['status' => 'active']);
```

**Parameters:**
- `$key` (string): Custom cache key

**Returns:** `Repository`

---

#### `clearCache()`
Clear all cache for this repository.

```php
$this->userRepository->clearCache();
```

**Returns:** `void`

## Query Builder Methods

#### `with(array $relations)`
Eager load relationships.

```php
$users = $this->userRepository
    ->with(['profile', 'posts'])
    ->all();
```

**Parameters:**
- `$relations` (array): Relationships to load

**Returns:** `Repository`

---

#### `orderBy($column, $direction = 'asc')`
Add order by clause.

```php
$users = $this->userRepository
    ->orderBy('created_at', 'desc')
    ->all();
```

**Parameters:**
- `$column` (string): Column name
- `$direction` (string): Sort direction (asc/desc)

**Returns:** `Repository`

---

#### `scopeQuery(\Closure $scope)`
Apply a scope query.

```php
$users = $this->userRepository
    ->scopeQuery(function ($query) {
        return $query->where('status', 'active');
    })
    ->all();
```

**Parameters:**
- `$scope` (Closure): Query scope closure

**Returns:** `Repository`

## Request Criteria

The `RequestCriteria` automatically handles HTTP request parameters for filtering, searching, and sorting.

### Search Parameters

#### Basic Search
```bash
GET /api/users?search=name:john
GET /api/users?search=email:gmail.com
```

#### Advanced Search
```bash
# Multiple fields with AND logic
GET /api/users?search=name:john;status:active&searchJoin=and

# Multiple fields with OR logic  
GET /api/users?search=name:john;email:gmail.com&searchJoin=or

# Like operator
GET /api/users?search=name:like:john

# Multiple operators
GET /api/users?searchFields=name:like;email:=;status:in
```

#### HashId Search
```bash
# Search by HashId
GET /api/users?search=id:abc123

# Multiple HashIds
GET /api/users?search=id:in:abc123,def456

# Foreign key HashIds
GET /api/posts?search=user_id:abc123
```

### Filter Parameters

#### Basic Filters
```bash
GET /api/users?filter=status:active
GET /api/users?filter=verified:true
```

#### Multiple Filters
```bash
GET /api/users?filter=status:active;verified:true
```

#### Date Filters
```bash
# Date ranges
GET /api/users?filter=created_at:date_between:2024-01-01,2024-12-31

# Specific dates
GET /api/users?filter=created_at:date_equals:2024-01-01
```

#### Number Filters
```bash
# Number ranges
GET /api/products?filter=price:between:100,500

# Comparisons
GET /api/products?filter=price:>=:100
```

### Include Parameters

#### Basic Includes
```bash
GET /api/users?include=profile
GET /api/users?include=profile,posts
```

#### Nested Includes
```bash
GET /api/users?include=posts.comments
GET /api/users?include=profile.country,posts.category
```

#### Count Includes
```bash
GET /api/users?include=posts_count
GET /api/users?include=posts_count,comments_count
```

### Ordering Parameters

#### Single Field
```bash
GET /api/users?orderBy=created_at&sortedBy=desc
GET /api/users?orderBy=name&sortedBy=asc
```

#### Multiple Fields
```bash
GET /api/users?orderBy=status,created_at&sortedBy=asc,desc
```

## Configuration Reference

### Cache Configuration

```php
'cache' => [
    'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
    'minutes' => env('REPOSITORY_CACHE_MINUTES', 60),
    'store' => env('REPOSITORY_CACHE_STORE', 'redis'),
    'prefix' => env('REPOSITORY_CACHE_PREFIX', 'repo_'),
    'tags' => [
        'enabled' => true,
        'auto_clear' => true,
    ],
],
```

### HashId Configuration

```php
'hashid' => [
    'enabled' => env('HASHID_ENABLED', true),
    'apiato_integration' => env('APIATO_HASHID_INTEGRATION', true),
    'auto_encode' => true,
    'auto_decode' => true,
    'cache_enabled' => true,
    'fallback_to_numeric' => true,
],
```

### Fractal Configuration

```php
'fractal' => [
    'serializer' => \League\Fractal\Serializer\DataArraySerializer::class,
    'params' => [
        'include' => 'include',
        'exclude' => 'exclude',
        'fields' => 'fields',
    ],
    'auto_includes' => true,
],
```

### Criteria Configuration

```php
'criteria' => [
    'params' => [
        'search' => 'search',
        'searchFields' => 'searchFields',
        'searchJoin' => 'searchJoin',
        'filter' => 'filter',
        'orderBy' => 'orderBy',
        'sortedBy' => 'sortedBy',
        'include' => 'include',
    ],
    'acceptedConditions' => [
        '=', '!=', '<>', '>', '<', '>=', '<=',
        'like', 'ilike', 'not_like',
        'in', 'not_in', 'notin',
        'between', 'not_between',
        'date_between', 'date_equals',
    ],
],
```

## Error Handling

### Repository Exceptions

```php
use Apiato\Repository\Exceptions\RepositoryException;

try {
    $user = $this->userRepository->create($data);
} catch (RepositoryException $e) {
    // Handle repository-specific errors
    Log::error('Repository error: ' . $e->getMessage());
}
```

### Model Not Found

```php
use Illuminate\Database\Eloquent\ModelNotFoundException;

try {
    $user = $this->userRepository->findOrFail($id);
} catch (ModelNotFoundException $e) {
    return response()->json(['error' => 'User not found'], 404);
}
```

### Validation Errors

```php
use Illuminate\Validation\ValidationException;

try {
    $user = $this->userRepository->create($data);
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
}
```

## Events

The repository fires events during CRUD operations:

### Available Events

- `RepositoryEntityCreating` - Before creating
- `RepositoryEntityCreated` - After creating
- `RepositoryEntityUpdating` - Before updating
- `RepositoryEntityUpdated` - After updating
- `RepositoryEntityDeleting` - Before deleting
- `RepositoryEntityDeleted` - After deleting

### Listening to Events

```php
// In EventServiceProvider
protected $listen = [
    'Apiato\Repository\Events\RepositoryEntityCreated' => [
        'App\Listeners\ClearUserCache',
        'App\Listeners\SendWelcomeEmail',
    ],
];

// Listener example
class ClearUserCache
{
    public function handle($event)
    {
        $model = $event->getModel();
        $repository = $event->getRepository();
        
        // Clear related caches
        Cache::tags(['users'])->flush();
    }
}
```

## Artisan Commands

### Make Repository

```bash
php artisan make:repository UserRepository --model=User
php artisan make:repository UserRepository --model=User --interface
```

### Make Criteria

```bash
php artisan make:criteria ActiveUsersCriteria
```

### Make Entity (Model + Repository)

```bash
php artisan make:entity User
```

### Clear Repository Cache

```bash
php artisan repository:clear-cache
php artisan repository:clear-cache --tags=users,posts
```

## Testing Helpers

### Repository Testing

```php
// Test basic operations
public function test_can_create_user(): void
{
    $user = $this->userRepository->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $this->assertInstanceOf(User::class, $user);
    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
}

// Test HashId operations
public function test_can_find_by_hash_id(): void
{
    $user = User::factory()->create();
    $hashId = $this->userRepository->encodeHashId($user->id);
    
    $found = $this->userRepository->findByHashId($hashId);

    $this->assertEquals($user->id, $found->id);
}

// Test caching
public function test_repository_caches_results(): void
{
    Cache::shouldReceive('remember')->once();
    
    $this->userRepository->all();
}
```

## Performance Tips

### Optimize Queries

```php
// Select only needed columns
$users = $this->userRepository
    ->query()
    ->select(['id', 'name', 'email'])
    ->get();

// Eager load relationships
$users = $this->userRepository
    ->with(['profile:id,user_id,bio'])
    ->all();

// Use pagination for large datasets
$users = $this->userRepository->paginate(50);
```

### Cache Strategies

```php
// Cache expensive queries
$activeUsers = $this->userRepository
    ->cacheMinutes(120)
    ->findWhere(['status' => 'active']);

// Use specific cache keys
$premiumUsers = $this->userRepository
    ->cacheKey('premium_users')
    ->findWhere(['type' => 'premium']);
```

### HashId Optimization

```php
// Batch decode HashIds
$ids = $this->userRepository->decodeMultipleHashIds($hashIds);

// Cache HashId conversions
$hashId = $this->userRepository->encodeHashId($id); // Cached automatically
```

## Next Steps

- [Installation Guide](installation-migration.md) - Get started
- [Repository Usage](repositories.md) - Learn the basics
- [Performance Guide](performance.md) - Optimize your implementation
- [Testing Guide](testing.md) - Test your repositories
