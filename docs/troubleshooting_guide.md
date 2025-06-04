# Troubleshooting - Common Issues & Solutions

Comprehensive troubleshooting guide for Apiato Repository with step-by-step solutions, debugging techniques, and preventive measures for common issues.

## 📚 Table of Contents

- [Installation Issues](#-installation-issues)
- [Repository Configuration Problems](#-repository-configuration-problems)
- [Caching Issues](#-caching-issues)
- [HashId Problems](#-hashid-problems)
- [Performance Issues](#-performance-issues)
- [Event System Issues](#-event-system-issues)
- [Validation Problems](#-validation-problems)
- [Advanced Debugging](#-advanced-debugging)

## 🔧 Installation Issues

### Issue: Package Not Found or Installation Fails

**Symptoms:**
```bash
Could not find package apiato/repository
Your requirements could not be resolved to an installable set of packages
```

**Solution:**
```bash
# 1. Clear composer cache
composer clear-cache

# 2. Update composer to latest version
composer self-update

# 3. Try installing with specific version
composer require apiato/repository:dev-main

# 4. If still failing, try with --no-scripts flag
composer require apiato/repository --no-scripts

# 5. Run scripts manually after installation
composer run-script post-autoload-dump
```

**Prevention:**
- Always use the latest Composer version
- Check minimum PHP version requirements (8.1+)
- Ensure sufficient memory for Composer (`memory_limit=512M`)

### Issue: Class Aliases Not Working

**Symptoms:**
```php
Class 'Prettus\Repository\Eloquent\BaseRepository' not found
Interface 'Prettus\Repository\Contracts\RepositoryInterface' not found
```

**Diagnosis:**
```bash
# Check if service provider is registered
php artisan config:show app.providers | grep Repository

# Check autoloader
composer dump-autoload -o

# Verify class exists
php artisan tinker
>>> class_exists('Apiato\Repository\Eloquent\BaseRepository')
```

**Solution:**
```php
// 1. Manually register service provider in config/app.php
'providers' => [
    // ...
    Apiato\Repository\Providers\RepositoryServiceProvider::class,
],

// 2. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer dump-autoload

// 3. Verify aliases are created
php artisan tinker
>>> class_exists('Prettus\Repository\Eloquent\BaseRepository')
// Should return true
```

### Issue: Migration from l5-repository Incomplete

**Symptoms:**
```php
Method not found: pushCriteria()
Property not found: $fieldSearchable
```

**Solution:**
```php
// 1. Check your repository extends the correct base class
class UserRepository extends BaseRepository // ✅ Correct
{
    // Your code
}

// NOT this:
class UserRepository extends Model // ❌ Wrong

// 2. Ensure you're importing from the right namespace
use Apiato\Repository\Eloquent\BaseRepository; // ✅ New
// NOT: use Prettus\Repository\Eloquent\BaseRepository; // ❌ Old (but should work via alias)

// 3. Verify all required methods exist
public function model()
{
    return User::class; // Required
}

public function boot()
{
    $this->pushCriteria(app(RequestCriteria::class)); // Optional but recommended
}
```

## ⚙️ Repository Configuration Problems

### Issue: Repository Not Found or Not Registered

**Symptoms:**
```php
Target class [App\Repositories\UserRepository] does not exist
```

**Diagnosis:**
```bash
# Check if repository file exists
ls -la app/Repositories/UserRepository.php

# Check namespace in file
head -n 10 app/Repositories/UserRepository.php

# Check if it's properly autoloaded
composer dump-autoload -v
```

**Solution:**
```php
// 1. Verify file location and namespace
<?php
namespace App\Repositories; // Must match folder structure

use Apiato\Repository\Eloquent\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }
}

// 2. Register in service provider if using manual registration
// app/Providers/RepositoryServiceProvider.php
public function register()
{
    $this->app->bind(
        \App\Repositories\UserRepository::class,
        \App\Repositories\UserRepository::class
    );
}

// 3. Regenerate autoloader
composer dump-autoload
```

### Issue: Model Class Not Found in Repository

**Symptoms:**
```php
Class 'App\Models\User' not found
Target class [App\Models\User] does not exist
```

**Solution:**
```php
// 1. Check model exists and has correct namespace
// app/Models/User.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // Model definition
}

// 2. Verify repository model() method returns correct class
public function model()
{
    return User::class; // ✅ Correct
    // return 'User';   // ❌ Wrong
    // return \User::class; // ❌ Wrong namespace
}

// 3. Add full namespace if needed
public function model()
{
    return \App\Models\User::class;
}
```

### Issue: Repository Methods Not Working

**Symptoms:**
```php
Call to undefined method find()
Call to undefined method findWhere()
Method paginate() not found
```

**Diagnosis:**
```php
// Check if repository extends BaseRepository
php artisan tinker
>>> $repo = app(\App\Repositories\UserRepository::class);
>>> get_parent_class($repo);
// Should return: "Apiato\Repository\Eloquent\BaseRepository"

>>> method_exists($repo, 'find');
// Should return: true
```

**Solution:**
```php
// 1. Ensure proper inheritance
class UserRepository extends BaseRepository // ✅ Correct
{
    // Implementation
}

// 2. Check for method conflicts
class UserRepository extends BaseRepository
{
    // Don't override base methods unless necessary
    // public function find($id) // ❌ Avoid unless you know what you're doing
}

// 3. Call makeModel() if overriding constructor
public function __construct(Application $app)
{
    parent::__construct($app); // ✅ Required
    // Your custom initialization
}
```

## 💾 Caching Issues

### Issue: Cache Not Working or Always Misses

**Symptoms:**
```php
// Cache hit rate is 0%
// Queries always hit database
// No performance improvement
```

**Diagnosis:**
```bash
# Check cache configuration
php artisan config:show cache.default
php artisan config:show repository.cache

# Test cache manually
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
// Should return: "value"

# Check cache keys
>>> Cache::getRedis()->keys('*repo*');
```

**Solution:**
```php
// 1. Verify cache is enabled
// config/repository.php
'cache' => [
    'enabled' => true, // Must be true
    'minutes' => 30,
    'repository' => 'redis', // Must match cache store
],

// 2. Check cache store configuration
// config/cache.php
'default' => 'redis',
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
],

// 3. Verify Redis connection
// .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

// 4. Test Redis connection
php artisan tinker
>>> \Illuminate\Support\Facades\Redis::ping();
// Should return: "+PONG"
```

### Issue: Cache Keys Colliding

**Symptoms:**
```php
// Different repositories returning same data
// Cache invalidation affecting wrong data
```

**Solution:**
```php
// 1. Set unique cache prefixes
// config/repository.php
'cache' => [
    'prefix' => env('REPOSITORY_CACHE_PREFIX', 'repo_' . env('APP_ENV')),
],

// 2. Use model-specific cache tags
class UserRepository extends BaseRepository
{
    protected $cacheTags = ['users', 'user_data'];
}

class PostRepository extends BaseRepository  
{
    protected $cacheTags = ['posts', 'post_data'];
}

// 3. Override cache key generation for uniqueness
protected function getCacheKey($method, $args = null)
{
    $key = parent::getCacheKey($method, $args);
    return static::class . ':' . $key;
}
```

### Issue: Cache Not Invalidating

**Symptoms:**
```php
// Stale data returned after updates
// Cache shows old data after delete
```

**Solution:**
```php
// 1. Verify cache cleaning is enabled
// config/repository.php
'cache' => [
    'clean' => [
        'enabled' => true,
        'on' => [
            'create' => true,
            'update' => true, 
            'delete' => true,
        ]
    ],
],

// 2. Check if events are firing
class UserRepository extends BaseRepository
{
    public function update(array $attributes, $id)
    {
        $result = parent::update($attributes, $id);
        
        // Manual cache clear if auto-clear not working
        $this->clearCache();
        
        return $result;
    }
    
    protected function clearCache()
    {
        if (method_exists($this, 'flushCache')) {
            $this->flushCache();
        }
        
        // Clear specific cache tags
        Cache::tags(['users'])->flush();
    }
}

// 3. Clear cache manually when needed
php artisan cache:clear
php artisan cache:forget repository_cache_key
```

## 🔑 HashId Problems

### Issue: HashIds Not Encoding/Decoding

**Symptoms:**
```php
// Regular IDs showing instead of HashIds
// HashId decode returning null
```

**Diagnosis:**
```bash
# Check HashId configuration
php artisan config:show repository.apiato.hashid_enabled

# Test HashId functions
php artisan tinker
>>> hashid_encode(123);
>>> hashid_decode('abc123');

# Check if HashId service is registered
>>> app()->bound('hashids');
```

**Solution:**
```php
// 1. Verify HashId is enabled
// config/repository.php
'apiato' => [
    'hashid_enabled' => true,
],

// 2. Install HashIds package if missing
composer require hashids/hashids

// 3. Configure HashIds properly
// config/hashids.php (if using dedicated config)
return [
    'default' => [
        'salt' => env('APP_KEY'),
        'length' => 6,
        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    ],
];

// 4. Register HashId service provider manually if needed
// config/app.php
'providers' => [
    // ...
    App\Providers\HashIdServiceProvider::class,
],
```

### Issue: HashId Decode Failures

**Symptoms:**
```php
// hashid_decode() returns null
// "Invalid HashId" errors
```

**Solution:**
```php
// 1. Check HashId format and length
$hashId = 'abc123';
if (strlen($hashId) < 4 || strlen($hashId) > 20) {
    // Invalid length
}

// 2. Verify salt consistency
// Make sure HASHID_SALT or APP_KEY hasn't changed
echo env('APP_KEY'); // Should be consistent

// 3. Add validation in repository
protected function processIdValue($value)
{
    if (is_string($value) && $this->looksLikeHashId($value)) {
        $decoded = hashid_decode($value);
        
        if ($decoded === null) {
            throw new \InvalidArgumentException("Invalid HashId: {$value}");
        }
        
        return $decoded;
    }
    
    return $value;
}

protected function looksLikeHashId(string $value): bool
{
    return !is_numeric($value) && 
           strlen($value) >= 4 && 
           preg_match('/^[a-zA-Z0-9]+$/', $value);
}
```

### Issue: HashId Conflicts in Multi-Tenant Setup

**Symptoms:**
```php
// Same HashId decoding to different IDs in different tenants
// Cross-tenant data access
```

**Solution:**
```php
// 1. Use tenant-specific salts
class TenantAwareRepository extends BaseRepository
{
    protected function getHashIdConnection(): string
    {
        $tenant = auth()->user()->tenant ?? 'default';
        return "tenant_{$tenant}";
    }
    
    protected function processIdValue($value)
    {
        if (is_string($value) && $this->looksLikeHashId($value)) {
            return hashid_decode($value, $this->getHashIdConnection());
        }
        
        return $value;
    }
}

// 2. Configure tenant-specific HashId connections
// config/hashids.php
'connections' => [
    'tenant_a' => [
        'salt' => env('APP_KEY') . '_tenant_a',
        'length' => 8,
    ],
    'tenant_b' => [
        'salt' => env('APP_KEY') . '_tenant_b',
        'length' => 8,
    ],
],
```

## ⚡ Performance Issues

### Issue: Slow Query Performance

**Symptoms:**
```php
// API responses taking > 1 second
// Database queries timing out
```

**Diagnosis:**
```bash
# Enable query logging
# config/database.php
'log' => true,

# Check slow query log
tail -f storage/logs/laravel.log | grep "select"

# Check database indexes
SHOW INDEX FROM users;
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

**Solution:**
```php
// 1. Add proper database indexes
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('status');
    $table->index(['status', 'created_at']); // Composite index
});

// 2. Optimize eager loading
$users = $this->repository
    ->with(['profile:id,user_id,avatar']) // Select specific columns
    ->findWhere(['status' => 'active']);

// 3. Use efficient queries
// Good: Use exists instead of count
$hasUsers = $this->repository->exists();

// Bad: Count when you only need to know if exists
$hasUsers = $this->repository->count() > 0;

// 4. Implement chunking for large datasets
$this->repository->chunk(1000, function($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

### Issue: Memory Leaks or High Memory Usage

**Symptoms:**
```php
// PHP Fatal error: Allowed memory size exhausted
// Memory usage climbing continuously
```

**Solution:**
```php
// 1. Use chunking for large datasets
public function processAllUsers()
{
    $this->repository->chunk(1000, function($users) {
        foreach ($users as $user) {
            $this->processUser($user);
        }
        
        // Force garbage collection periodically
        gc_collect_cycles();
    });
}

// 2. Unset large variables
public function processData()
{
    $data = $this->repository->all();
    
    // Process data
    foreach ($data as $item) {
        // Process item
    }
    
    // Free memory
    unset($data);
    gc_collect_cycles();
}

// 3. Use generators for streaming
public function getDataStream()
{
    $page = 1;
    $perPage = 1000;
    
    do {
        $users = $this->repository->paginate($perPage, ['*'], 'page', $page);
        
        foreach ($users as $user) {
            yield $user;
        }
        
        $page++;
        unset($users);
        
    } while ($users->hasMorePages());
}
```

### Issue: N+1 Query Problem

**Symptoms:**
```php
// Hundreds of queries for simple operations
// Query count increasing with result count
```

**Diagnosis:**
```php
// Enable query counting
DB::enableQueryLog();

$users = $this->repository->all();
foreach ($users as $user) {
    echo $user->profile->bio; // This might cause N+1
}

$queries = DB::getQueryLog();
echo "Total queries: " . count($queries);
```

**Solution:**
```php
// 1. Use eager loading
$users = $this->repository
    ->with(['profile', 'roles']) // Load relationships upfront
    ->all();

// 2. Use RequestCriteria with automatic eager loading
// GET /api/users?with=profile,roles

// 3. Load relationships after fetching if needed
$users = $this->repository->all();
$users->load(['profile', 'roles']);

// 4. Use specific column selection
$users = $this->repository
    ->with(['profile:id,user_id,bio,avatar'])
    ->all();
```

## 🎯 Event System Issues

### Issue: Events Not Firing

**Symptoms:**
```php
// Repository events not triggering
// Listeners not executing
```

**Diagnosis:**
```php
// Check if events are registered
php artisan event:list | grep Repository

// Test event firing manually
event(new \Apiato\Repository\Events\RepositoryEntityCreated($repository, $model));

// Check if listeners are registered
// app/Providers/EventServiceProvider.php
```

**Solution:**
```php
// 1. Register events in EventServiceProvider
// app/Providers/EventServiceProvider.php
protected $listen = [
    'Apiato\Repository\Events\RepositoryEntityCreated' => [
        'App\Listeners\ClearCache',
        'App\Listeners\SendNotification',
    ],
];

// 2. Enable events in repository
class UserRepository extends BaseRepository
{
    protected $eventsEnabled = true; // Ensure events are enabled
    
    public function boot()
    {
        parent::boot();
        // Additional setup
    }
}

// 3. Check listener class exists and is correct
class ClearCache
{
    public function handle($event)
    {
        $model = $event->getModel();
        $repository = $event->getRepository();
        
        // Clear cache logic
        Cache::tags(['users'])->flush();
    }
}

// 4. Clear event cache
php artisan event:clear
php artisan config:clear
```

### Issue: Event Performance Impact

**Symptoms:**
```php
// Slow repository operations after adding events
// Timeouts during bulk operations
```

**Solution:**
```php
// 1. Use queued listeners for heavy operations
class SendNotificationListener implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle($event)
    {
        // Heavy operation that will be queued
        Mail::to($event->getModel()->email)->send(new WelcomeEmail());
    }
}

// 2. Disable events for bulk operations
public function bulkCreate(array $records)
{
    // Temporarily disable events
    $originalEvents = $this->eventsEnabled;
    $this->eventsEnabled = false;
    
    foreach ($records as $record) {
        $this->create($record);
    }
    
    // Re-enable events
    $this->eventsEnabled = $originalEvents;
    
    // Fire bulk event instead
    event(new BulkUsersCreated($records));
}

// 3. Use event batching
protected $eventBatch = [];

protected function fireEvent($event)
{
    $this->eventBatch[] = $event;
    
    if (count($this->eventBatch) >= 100) {
        $this->processBatch();
    }
}

protected function processBatch()
{
    event(new BatchRepositoryEvents($this->eventBatch));
    $this->eventBatch = [];
}
```

## ✅ Validation Problems

### Issue: Validation Not Working

**Symptoms:**
```php
// Invalid data being saved
// No validation errors thrown
```

**Diagnosis:**
```php
// Check if validation is enabled
php artisan config:show repository.validation.enabled

// Test validator manually
$validator = app(\App\Validators\UserValidator::class);
$validator->with(['name' => '']);
echo $validator->passes() ? 'Valid' : 'Invalid';
```

**Solution:**
```php
// 1. Ensure validation is enabled
// config/repository.php
'validation' => [
    'enabled' => true,
],

// 2. Check repository has validator
class UserRepository extends BaseRepository
{
    public function validator()
    {
        return UserValidator::class; // Must return validator class
    }
}

// 3. Verify validator implementation
class UserValidator implements ValidatorInterface
{
    protected $rules = [
        'create' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ],
    ];
    
    public function with(array $input)
    {
        $this->data = $input;
        return $this;
    }
    
    public function passes($action = null)
    {
        $rules = $this->rules[$action] ?? [];
        $validator = \Validator::make($this->data, $rules);
        
        if ($validator->fails()) {
            $this->errors = $validator->errors()->toArray();
            return false;
        }
        
        return true;
    }
}
```

### Issue: Validation Rules Not Applied

**Symptoms:**
```php
// Some validation rules ignored
// Inconsistent validation behavior
```

**Solution:**
```php
// 1. Check rule syntax
protected $rules = [
    'create' => [
        'email' => 'required|email|unique:users,email', // ✅ Correct
        // 'email' => 'required,email,unique:users,email', // ❌ Wrong separator
    ],
];

// 2. Verify rule keys match action
public function create(array $attributes)
{
    // This will look for 'create' rules
    return parent::create($attributes);
}

// 3. Handle dynamic rules properly
public function passes($action = null)
{
    $rules = $this->rules[$action] ?? $this->rules['default'] ?? [];
    
    // Apply dynamic rules
    if ($action === 'update' && isset($this->data['id'])) {
        $rules = $this->applyUpdateRules($rules);
    }
    
    $validator = \Validator::make($this->data, $rules);
    return $validator->passes();
}
```

## 🔍 Advanced Debugging

### Debug Mode Setup

```php
// Enable comprehensive debugging
// .env
APP_DEBUG=true
REPOSITORY_LOGGING_ENABLED=true
REPOSITORY_LOG_LEVEL=debug
REPOSITORY_LOG_QUERIES=true
REPOSITORY_PERFORMANCE_MONITORING=true

// Enable SQL query logging
DB::enableQueryLog();

// Your repository operations
$users = $repository->paginate(15);

// Check executed queries
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    echo $query['query'] . "\n";
    echo "Time: " . $query['time'] . "ms\n";
    print_r($query['bindings']);
    echo "\n---\n";
}
```

### Performance Profiling

```php
// Create debug repository wrapper
class DebugRepository extends BaseRepository
{
    protected $debugInfo = [];
    
    public function __call($method, $arguments)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = parent::__call($method, $arguments);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $this->debugInfo[] = [
            'method' => $method,
            'arguments' => $arguments,
            'execution_time' => ($endTime - $startTime) * 1000,
            'memory_used' => $endMemory - $startMemory,
            'memory_peak' => memory_get_peak_usage(),
        ];
        
        return $result;
    }
    
    public function getDebugInfo()
    {
        return $this->debugInfo;
    }
}

// Usage
$debugRepo = new DebugRepository(app());
$users = $debugRepo->paginate(15);
print_r($debugRepo->getDebugInfo());
```

### Cache Debugging

```php
// Debug cache operations
class CacheDebugRepository extends BaseRepository
{
    protected function getCacheKey($method, $args = null)
    {
        $key = parent::getCacheKey($method, $args);
        
        Log::debug("Cache key generated", [
            'method' => $method,
            'args' => $args,
            'key' => $key,
            'exists' => Cache::has($key),
        ]);
        
        return $key;
    }
    
    public function getCachedResult($key, $callback)
    {
        $startTime = microtime(true);
        
        if (Cache::has($key)) {
            $result = Cache::get($key);
            $hit = true;
        } else {
            $result = $callback();
            Cache::put($key, $result, $this->cacheMinutes);
            $hit = false;
        }
        
        $endTime = microtime(true);
        
        Log::debug("Cache operation", [
            'key' => $key,
            'hit' => $hit,
            'time' => ($endTime - $startTime) * 1000,
            'result_size' => strlen(serialize($result)),
        ]);
        
        return $result;
    }
}
```

### Repository Health Check

```php
class RepositoryHealthCheck
{
    public function checkHealth($repositoryClass): array
    {
        $repository = app($repositoryClass);
        $health = [];
        
        // Check if repository extends correct base class
        $health['extends_base_repository'] = $repository instanceof BaseRepository;
        
        // Check if model is properly configured
        try {
            $model = $repository->makeModel();
            $health['model_configured'] = true;
            $health['model_class'] = get_class($model);
        } catch (\Exception $e) {
            $health['model_configured'] = false;
            $health['model_error'] = $e->getMessage();
        }
        
        // Check database connection
        try {
            $repository->count();
            $health['database_connection'] = true;
        } catch (\Exception $e) {
            $health['database_connection'] = false;
            $health['database_error'] = $e->getMessage();
        }
        
        // Check cache functionality
        if (config('repository.cache.enabled')) {
            try {
                $cacheKey = 'health_check_' . time();
                Cache::put($cacheKey, 'test', 1);
                $cached = Cache::get($cacheKey);
                $health['cache_working'] = $cached === 'test';
                Cache::forget($cacheKey);
            } catch (\Exception $e) {
                $health['cache_working'] = false;
                $health['cache_error'] = $e->getMessage();
            }
        }
        
        // Check if searchable fields are valid
        if (property_exists($repository, 'fieldSearchable')) {
            $searchableFields = $repository->getFieldsSearchable();
            $modelColumns = \Schema::getColumnListing($repository->getModel()->getTable());
            
            $invalidFields = array_diff(array_keys($searchableFields), $modelColumns);
            $health['searchable_fields_valid'] = empty($invalidFields);
            $health['invalid_searchable_fields'] = $invalidFields;
        }
        
        return $health;
    }
}

// Usage
$healthCheck = new RepositoryHealthCheck();
$health = $healthCheck->checkHealth(\App\Repositories\UserRepository::class);
print_r($health);
```

### Common Error Solutions Quick Reference

| Error | Quick Fix |
|-------|-----------|
| `Class not found` | `composer dump-autoload` |
| `Method not found` | Check repository extends `BaseRepository` |
| `Cache not working` | Verify Redis connection and config |
| `HashId decode null` | Check `APP_KEY` consistency |
| `Validation not working` | Ensure validator is properly configured |
| `Events not firing` | Register events in `EventServiceProvider` |
| `Slow queries` | Add database indexes |
| `Memory issues` | Use chunking for large datasets |
| `N+1 queries` | Use eager loading with `with()` |
| `Connection errors` | Check database configuration |

### Getting Help

If you're still experiencing issues after trying these solutions:

1. **Check the GitHub Issues**: https://github.com/apiato/repository/issues
2. **Create a Minimal Reproduction**: Include only the essential code to reproduce the issue
3. **Provide Environment Details**: PHP version, Laravel version, package version
4. **Include Error Messages**: Full stack traces and error messages
5. **Share Configuration**: Relevant config files (with sensitive data removed)

---

**Next:** Learn about **[Migration Guide](migration.md)** for detailed steps to migrate from other repository packages.