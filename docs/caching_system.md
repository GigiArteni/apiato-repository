# Caching System - Performance Optimization

Complete guide to Apiato Repository's intelligent caching system with automatic cache invalidation, performance optimization, and advanced caching strategies.

## 📚 Table of Contents

- [Understanding Repository Caching](#-understanding-repository-caching)
- [Basic Cache Configuration](#-basic-cache-configuration)
- [Automatic Cache Management](#-automatic-cache-management)
- [Advanced Caching Strategies](#-advanced-caching-strategies)
- [Cache Invalidation](#-cache-invalidation)
- [Performance Monitoring](#-performance-monitoring)
- [Redis Integration](#-redis-integration)
- [Best Practices](#-best-practices)

## 🧠 Understanding Repository Caching

Apiato Repository includes an intelligent caching layer that automatically caches query results and invalidates cache when data changes, providing 40-80% performance improvements with zero configuration.

### How It Works

```php
// First call - queries database and caches result
$users = $repository->all(); // 120ms (database query)

// Subsequent calls - served from cache  
$users = $repository->all(); // 5ms (cache hit)

// Automatic cache invalidation
$repository->create($newUser); // Cache cleared automatically
$users = $repository->all(); // 120ms (fresh database query)
```

### Cache Layers

```php
/**
 * Multi-level caching architecture
 */
1. Query Result Cache    // Caches database query results
2. Model Instance Cache  // Caches individual model instances  
3. Relationship Cache    // Caches relationship data
4. Aggregation Cache     // Caches count/sum/avg queries
5. Search Result Cache   // Caches search and filter results
```

## ⚙️ Basic Cache Configuration

### Default Configuration

```php
// config/repository.php - Enhanced defaults
return [
    'cache' => [
        'enabled' => true,           // Auto-enabled for better performance
        'minutes' => 30,             // Cache TTL
        'repository' => 'cache',     // Cache store
        'clean' => [
            'enabled' => true,       // Auto-invalidation enabled
            'on' => [
                'create' => true,    // Clear cache on create
                'update' => true,    // Clear cache on update  
                'delete' => true,    // Clear cache on delete
            ]
        ],
        'params' => [
            'skipCache' => 'skipCache', // URL parameter to skip cache
        ],
        'allowed' => [
            'only' => null,          // Cache only specific methods
            'except' => null,        // Cache all except specific methods
        ],
        'tags' => [
            'enabled' => true,       // Cache tagging support
            'generator' => 'model',  // Tag generation strategy
        ],
    ],
];
```

### Repository-Level Cache Settings

```php
class UserRepository extends BaseRepository
{
    // Override cache settings for this repository
    protected $cacheMinutes = 60; // Cache for 1 hour
    
    public function model()
    {
        return User::class;
    }

    /**
     * Methods that should always skip cache
     */
    protected $skipCacheMethods = [
        'getRealtimeData',
        'getCurrentOnlineUsers',
    ];

    /**
     * Get real-time data (always skip cache)
     */
    public function getRealtimeData()
    {
        return $this->skipCache()->findWhere(['status' => 'online']);
    }

    /**
     * Get cached active users
     */
    public function getActiveUsers()
    {
        // This will be cached automatically
        return $this->findWhere(['status' => 'active']);
    }

    /**
     * Force cache refresh
     */
    public function getActiveUsersRefresh()
    {
        return $this->skipCache()->findWhere(['status' => 'active']);
    }
}
```

## 🤖 Automatic Cache Management

### Auto-Cache Key Generation

```php
// Automatic cache key generation based on:
// - Repository class name
// - Method name  
// - Method parameters
// - Applied criteria
// - Query conditions

// Example cache keys generated automatically:
"App\Repositories\UserRepository@all-[]"
"App\Repositories\UserRepository@findWhere-[{\"status\":\"active\"}]"
"App\Repositories\UserRepository@paginate-[15,[\"*\"]]"
"App\Repositories\UserRepository@find-[123]"
```

### Smart Cache Invalidation

```php
class UserRepository extends BaseRepository
{
    public function createUser(array $data)
    {
        // Create user
        $user = $this->create($data);
        
        // These caches are cleared automatically:
        // - all()
        // - paginate()
        // - findWhere(['status' => 'active']) if user is active
        // - count() aggregations
        // - Related repository caches (posts, etc.)
        
        return $user;
    }

    public function updateUserStatus($id, $status)
    {
        $user = $this->update(['status' => $status], $id);
        
        // Intelligent cache clearing:
        // - Clears caches that might be affected by status change
        // - Preserves unrelated caches
        // - Updates relationship caches
        
        return $user;
    }
}
```

### Cache Tags for Smart Invalidation

```php
// Automatic cache tagging by model
// Tags generated: ['users', 'user:123', 'active_users', 'status:active']

$activeUsers = $repository->findWhere(['status' => 'active']);
// Cache tags: ['users', 'status:active', 'active_users']

$specificUser = $repository->find(123);
// Cache tags: ['users', 'user:123']

// When user 123 is updated, only relevant caches are cleared:
$repository->update(['name' => 'New Name'], 123);
// Clears: ['user:123'] tagged caches
// Preserves: ['status:active'] caches (if status didn't change)
```

## 🔧 Advanced Caching Strategies

### Method-Specific Caching

```php
class UserRepository extends BaseRepository
{
    /**
     * Cache expensive aggregations longer
     */
    public function getUserStatistics()
    {
        $cacheKey = 'user_statistics_' . date('Y-m-d-H'); // Hourly cache
        
        return Cache::remember($cacheKey, 3600, function() {
            return [
                'total_users' => $this->count(),
                'active_users' => $this->findWhere(['status' => 'active'])->count(),
                'new_today' => $this->findWhere([
                    ['created_at', '>=', now()->startOfDay()]
                ])->count(),
                'average_posts' => $this->model
                    ->withCount('posts')
                    ->get()
                    ->avg('posts_count'),
            ];
        });
    }

    /**
     * Cache popular content longer
     */
    public function getPopularUsers($limit = 10)
    {
        $cacheKey = "popular_users_{$limit}";
        
        return Cache::remember($cacheKey, 1440, function() use ($limit) { // 24 hours
            return $this->scopeQuery(function($query) {
                return $query->withCount(['posts', 'followers'])
                            ->orderByRaw('posts_count + followers_count DESC');
            })->paginate($limit);
        });
    }

    /**
     * Cache with custom expiration based on data freshness
     */
    public function getRecentActivity()
    {
        // Shorter cache for recent data, longer for older data
        $now = now();
        $cacheMinutes = $now->hour < 9 || $now->hour > 17 ? 60 : 15; // Less frequent updates outside business hours
        
        return Cache::remember('recent_activity', $cacheMinutes, function() {
            return $this->with(['posts' => function($query) {
                $query->where('created_at', '>=', now()->subHours(24));
            }])->findWhere([
                ['last_activity_at', '>=', now()->subHours(24)]
            ]);
        });
    }
}
```

### Hierarchical Caching

```php
class PostRepository extends BaseRepository
{
    /**
     * Multi-level cache hierarchy
     */
    public function getPostsWithCache($categoryId = null, $authorId = null, $page = 1)
    {
        // Level 1: Category cache
        if ($categoryId) {
            $categoryKey = "posts_category_{$categoryId}";
            $posts = Cache::remember($categoryKey, 60, function() use ($categoryId) {
                return $this->findWhere(['category_id' => $categoryId]);
            });
        }

        // Level 2: Author cache  
        if ($authorId) {
            $authorKey = "posts_author_{$authorId}";
            $posts = Cache::remember($authorKey, 30, function() use ($authorId) {
                return $this->findWhere(['user_id' => $authorId]);
            });
        }

        // Level 3: Combined cache
        if ($categoryId && $authorId) {
            $combinedKey = "posts_category_{$categoryId}_author_{$authorId}";
            $posts = Cache::remember($combinedKey, 45, function() use ($categoryId, $authorId) {
                return $this->findWhere([
                    'category_id' => $categoryId,
                    'user_id' => $authorId
                ]);
            });
        }

        // Level 4: Paginated cache
        $pageKey = "{$combinedKey}_page_{$page}";
        return Cache::remember($pageKey, 15, function() use ($posts, $page) {
            return $posts->forPage($page, 15);
        });
    }
}
```

### Cache Warming

```php
class UserRepository extends BaseRepository
{
    /**
     * Warm cache with frequently accessed data
     */
    public function warmCache()
    {
        // Warm popular queries
        $this->getActiveUsers();           // Cache active users
        $this->getPopularUsers();          // Cache popular users  
        $this->getUserStatistics();       // Cache statistics
        
        // Warm paginated results
        for ($page = 1; $page <= 5; $page++) {
            $this->paginate(15, ['*'], 'page', $page);
        }

        // Warm common searches
        $commonSearches = ['admin', 'manager', 'author'];
        foreach ($commonSearches as $search) {
            $this->findWhere(['role' => $search]);
        }
    }

    /**
     * Schedule cache warming
     */
    public function scheduleWarmCache()
    {
        // Schedule via Laravel's task scheduler
        \Artisan::call('cache:warm-repositories');
    }
}

// Console command for cache warming
// app/Console/Commands/WarmRepositoryCache.php
class WarmRepositoryCache extends Command
{
    protected $signature = 'cache:warm-repositories';
    
    public function handle()
    {
        $repositories = [
            UserRepository::class,
            PostRepository::class,
            CategoryRepository::class,
        ];

        foreach ($repositories as $repositoryClass) {
            $repository = app($repositoryClass);
            if (method_exists($repository, 'warmCache')) {
                $repository->warmCache();
                $this->info("Warmed cache for {$repositoryClass}");
            }
        }
    }
}
```

## 🗑️ Cache Invalidation

### Automatic Invalidation

```php
class UserRepository extends BaseRepository
{
    /**
     * Automatic cache invalidation on data changes
     */
    public function updateUserRole($userId, $roleId)
    {
        $user = $this->update(['role_id' => $roleId], $userId);
        
        // Automatically clears:
        // ✅ All user list caches
        // ✅ Role-specific caches
        // ✅ User statistics caches
        // ✅ Related repository caches
        
        return $user;
    }

    /**
     * Manual cache invalidation for complex scenarios
     */
    public function complexUserUpdate($userId, array $data)
    {
        $user = $this->update($data, $userId);
        
        // Manual cache clearing for specific scenarios
        if (isset($data['status'])) {
            Cache::tags(['users', 'status:' . $data['status']])->flush();
        }
        
        if (isset($data['department_id'])) {
            Cache::tags(['department:' . $data['department_id']])->flush();
        }
        
        // Clear related caches
        Cache::forget('user_statistics');
        Cache::forget('popular_users');
        
        return $user;
    }
}
```

### Selective Cache Invalidation

```php
class PostRepository extends BaseRepository
{
    /**
     * Smart invalidation based on what changed
     */
    public function updatePost($id, array $data)
    {
        $post = $this->find($id);
        $oldCategoryId = $post->category_id;
        $oldStatus = $post->status;
        
        $updatedPost = $this->update($data, $id);
        
        // Clear caches selectively based on changes
        $this->invalidatePostCaches($updatedPost, [
            'old_category_id' => $oldCategoryId,
            'old_status' => $oldStatus,
            'changed_fields' => array_keys($data),
        ]);
        
        return $updatedPost;
    }

    protected function invalidatePostCaches($post, array $context)
    {
        $tagsToFlush = ['posts', "post:{$post->id}"];
        
        // Category changed - clear old and new category caches
        if (isset($context['old_category_id']) && 
            $context['old_category_id'] !== $post->category_id) {
            $tagsToFlush[] = "category:{$context['old_category_id']}";
            $tagsToFlush[] = "category:{$post->category_id}";
        }
        
        // Status changed - clear status-specific caches
        if (isset($context['old_status']) && 
            $context['old_status'] !== $post->status) {
            $tagsToFlush[] = "status:{$context['old_status']}";
            $tagsToFlush[] = "status:{$post->status}";
        }
        
        // Author-specific caches
        $tagsToFlush[] = "author:{$post->user_id}";
        
        // Clear tagged caches
        Cache::tags($tagsToFlush)->flush();
        
        // Clear specific keys if content changed
        if (in_array('content', $context['changed_fields'])) {
            Cache::forget("post_search_index");
            Cache::forget("popular_posts");
        }
    }
}
```

### Batch Cache Operations

```php
class UserRepository extends BaseRepository
{
    /**
     * Efficient batch operations with cache management
     */
    public function batchUpdateUsers(array $updates)
    {
        // Collect affected cache tags
        $affectedTags = collect();
        $affectedKeys = collect();
        
        foreach ($updates as $userId => $data) {
            $user = $this->find($userId);
            
            // Track what caches will be affected
            $affectedTags->push("user:{$userId}");
            
            if (isset($data['status'])) {
                $affectedTags->push("status:{$user->status}");
                $affectedTags->push("status:{$data['status']}");
            }
            
            if (isset($data['role_id'])) {
                $affectedTags->push("role:{$user->role_id}");
                $affectedTags->push("role:{$data['role_id']}");
            }
        }
        
        // Perform batch update
        foreach ($updates as $userId => $data) {
            $this->skipCache()->update($data, $userId);
        }
        
        // Batch clear affected caches
        Cache::tags($affectedTags->unique()->all())->flush();
        
        // Clear general caches
        Cache::tags(['users'])->flush();
        
        return true;
    }

    /**
     * Efficient bulk creation with cache warming
     */
    public function bulkCreateUsers(array $usersData)
    {
        // Create without caching to avoid individual cache operations
        $users = collect($usersData)->map(function($data) {
            return $this->skipCache()->create($data);
        });
        
        // Clear all user-related caches
        Cache::tags(['users'])->flush();
        
        // Warm cache with new data
        $this->warmCache();
        
        return $users;
    }
}
```

## 📊 Performance Monitoring

### Cache Hit Rate Monitoring

```php
class CacheMonitoringRepository extends BaseRepository
{
    protected $cacheHits = 0;
    protected $cacheMisses = 0;
    
    public function getCacheKey($method, $args = null)
    {
        $key = parent::getCacheKey($method, $args);
        
        // Monitor cache performance
        if (Cache::has($key)) {
            $this->cacheHits++;
            Log::info("Cache HIT: {$key}");
        } else {
            $this->cacheMisses++;
            Log::info("Cache MISS: {$key}");
        }
        
        return $key;
    }
    
    /**
     * Get cache performance metrics
     */
    public function getCacheMetrics(): array
    {
        $total = $this->cacheHits + $this->cacheMisses;
        $hitRate = $total > 0 ? ($this->cacheHits / $total) * 100 : 0;
        
        return [
            'hits' => $this->cacheHits,
            'misses' => $this->cacheMisses,
            'total' => $total,
            'hit_rate' => round($hitRate, 2),
        ];
    }
}
```

### Cache Performance Dashboard

```php
// app/Http/Controllers/Admin/CacheController.php
class CacheController extends Controller
{
    public function dashboard()
    {
        $metrics = [
            'cache_size' => $this->getCacheSize(),
            'hit_rates' => $this->getHitRates(),
            'top_cached_queries' => $this->getTopCachedQueries(),
            'cache_distribution' => $this->getCacheDistribution(),
            'invalidation_frequency' => $this->getInvalidationFrequency(),
        ];
        
        return view('admin.cache.dashboard', compact('metrics'));
    }
    
    protected function getCacheSize(): array
    {
        return [
            'total_keys' => Cache::getRedis()->dbSize(),
            'memory_usage' => $this->formatBytes(
                Cache::getRedis()->info()['used_memory']
            ),
            'memory_peak' => $this->formatBytes(
                Cache::getRedis()->info()['used_memory_peak']
            ),
        ];
    }
    
    protected function getHitRates(): array
    {
        $info = Cache::getRedis()->info();
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return [
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0,
        ];
    }
    
    protected function getTopCachedQueries(): array
    {
        // Get most frequently accessed cache keys
        return Cache::getRedis()->keys('*Repository*')
            ->take(20)
            ->map(function($key) {
                return [
                    'key' => $key,
                    'ttl' => Cache::getRedis()->ttl($key),
                    'size' => strlen(Cache::get($key)),
                    'hits' => Cache::getRedis()->object('idletime', $key),
                ];
            })
            ->sortByDesc('hits');
    }
}
```

## 🔴 Redis Integration

### Redis Configuration

```php
// config/cache.php - Optimized for repository caching
return [
    'default' => 'redis',
    
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
    ],
    
    'prefix' => env('CACHE_PREFIX', 'apiato_repo'),
];

// config/database.php - Redis optimization
'redis' => [
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
            'serializer' => 'php', // Fastest serialization
            'compression' => 'lz4', // Efficient compression
        ],
    ],
],
```

### Redis Clustering

```php
// config/cache.php - Redis cluster configuration
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'lock_connection' => 'default',
    'options' => [
        'cluster' => 'redis',
        'options' => [
            'cluster' => [
                'redis-cluster-01:6379',
                'redis-cluster-02:6379', 
                'redis-cluster-03:6379',
            ],
        ],
    ],
],
```

### Advanced Redis Features

```php
class RedisOptimizedRepository extends BaseRepository
{
    /**
     * Use Redis sets for efficient tag management
     */
    protected function getCacheTags($model = null): array
    {
        $tags = parent::getCacheTags($model);
        
        // Store tag relationships in Redis sets
        foreach ($tags as $tag) {
            Redis::sadd("cache_tag:{$tag}", $this->getCacheKey());
        }
        
        return $tags;
    }
    
    /**
     * Efficient tag-based invalidation using Redis
     */
    public function invalidateByTags(array $tags)
    {
        $pipeline = Redis::pipeline();
        
        foreach ($tags as $tag) {
            // Get all keys with this tag
            $keys = Redis::smembers("cache_tag:{$tag}");
            
            // Delete the keys
            if (!empty($keys)) {
                $pipeline->del($keys);
            }
            
            // Remove the tag set
            $pipeline->del("cache_tag:{$tag}");
        }
        
        $pipeline->execute();
    }
    
    /**
     * Redis Lua script for atomic cache operations
     */
    public function atomicCacheUpdate($key, $value, $tags = [])
    {
        $script = "
            local key = KEYS[1]
            local value = ARGV[1]
            local ttl = ARGV[2]
            
            -- Set the cache value
            redis.call('setex', key, ttl, value)
            
            -- Add to tag sets
            for i = 3, #ARGV do
                redis.call('sadd', 'cache_tag:' .. ARGV[i], key)
            end
            
            return 'OK'
        ";
        
        $params = array_merge([$key], [$value, 3600], $tags);
        
        return Redis::eval($script, 1, ...$params);
    }
}
```

## 💡 Best Practices

### Cache Strategy Guidelines

```php
/**
 * Cache TTL Guidelines
 */
class CacheTTLStrategy
{
    const REALTIME_DATA = 0;        // No cache
    const DYNAMIC_DATA = 300;       // 5 minutes
    const SEMI_STATIC = 1800;       // 30 minutes  
    const STATIC_DATA = 3600;       // 1 hour
    const REFERENCE_DATA = 86400;   // 24 hours
    const RARELY_CHANGED = 604800;  // 7 days
    
    public static function getTTL($dataType): int
    {
        return match($dataType) {
            'user_session' => self::REALTIME_DATA,
            'user_activity' => self::DYNAMIC_DATA,
            'post_list' => self::SEMI_STATIC,
            'user_profile' => self::STATIC_DATA,
            'categories' => self::REFERENCE_DATA,
            'system_config' => self::RARELY_CHANGED,
            default => self::SEMI_STATIC,
        };
    }
}

class UserRepository extends BaseRepository
{
    public function getUserProfile($id)
    {
        $ttl = CacheTTLStrategy::getTTL('user_profile');
        
        return Cache::remember("user_profile_{$id}", $ttl, function() use ($id) {
            return $this->with(['profile', 'roles'])->find($id);
        });
    }
}
```

### Cache Key Naming Conventions

```php
class CacheKeyGenerator
{
    /**
     * Standardized cache key format:
     * {app}:{version}:{repository}:{method}:{parameters}:{hash}
     */
    public static function generate($repository, $method, $params = []): string
    {
        $app = config('app.name');
        $version = config('app.version', 'v1');
        $repoName = class_basename($repository);
        $paramHash = md5(serialize($params));
        
        return "{$app}:{$version}:{$repoName}:{$method}:{$paramHash}";
    }
    
    /**
     * Generate tag-based keys for easy invalidation
     */
    public static function generateWithTags($repository, $method, $params = []): array
    {
        $baseKey = self::generate($repository, $method, $params);
        
        $tags = [
            strtolower(class_basename($repository)),
            $method,
        ];
        
        // Add parameter-based tags
        foreach ($params as $key => $value) {
            if (is_scalar($value)) {
                $tags[] = "{$key}:{$value}";
            }
        }
        
        return [
            'key' => $baseKey,
            'tags' => $tags,
        ];
    }
}
```

### Memory Management

```php
class MemoryEfficientRepository extends BaseRepository
{
    /**
     * Prevent cache memory bloat
     */
    public function getLargeDataset($filters = [])
    {
        // Don't cache large datasets
        if ($this->isLargeDataset($filters)) {
            return $this->skipCache()->findWhere($filters);
        }
        
        // Cache smaller datasets
        return $this->findWhere($filters);
    }
    
    protected function isLargeDataset($filters): bool
    {
        // Estimate result size
        $estimatedCount = $this->skipCache()
            ->scopeQuery(function($query) use ($filters) {
                foreach ($filters as $key => $value) {
                    $query->where($key, $value);
                }
                return $query;
            })
            ->count();
            
        return $estimatedCount > 1000; // Don't cache > 1000 records
    }
    
    /**
     * Compress large cache values
     */
    protected function setCacheValue($key, $value, $ttl)
    {
        $serialized = serialize($value);
        
        // Compress if value is large
        if (strlen($serialized) > 10240) { // 10KB
            $serialized = gzcompress($serialized, 6);
            $key .= ':compressed';
        }
        
        return Cache::put($key, $serialized, $ttl);
    }
    
    protected function getCacheValue($key)
    {
        $value = Cache::get($key);
        
        // Decompress if needed
        if (str_ends_with($key, ':compressed')) {
            $value = gzuncompress($value);
        }
        
        return unserialize($value);
    }
}
```

### Cache Monitoring & Alerts

```php
class CacheMonitor
{
    /**
     * Monitor cache health and send alerts
     */
    public function checkCacheHealth(): array
    {
        $metrics = [
            'hit_rate' => $this->getHitRate(),
            'memory_usage' => $this->getMemoryUsage(),
            'connection_status' => $this->checkConnection(),
            'expired_keys' => $this->getExpiredKeysCount(),
        ];
        
        // Send alerts if thresholds exceeded
        if ($metrics['hit_rate'] < 80) {
            $this->sendAlert('Low cache hit rate', $metrics);
        }
        
        if ($metrics['memory_usage'] > 90) {
            $this->sendAlert('High memory usage', $metrics);
        }
        
        return $metrics;
    }
    
    protected function sendAlert($message, $data)
    {
        Log::warning("Cache Alert: {$message}", $data);
        
        // Send to monitoring service
        if (config('services.monitoring.enabled')) {
            Http::post(config('services.monitoring.webhook'), [
                'alert' => $message,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
}
```

---

**Next:** Learn about **[HashId Integration](hashids.md)** for automatic ID encoding and decoding.