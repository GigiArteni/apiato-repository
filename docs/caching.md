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
