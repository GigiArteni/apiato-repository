# Caching & Performance: Scale with Confidence

Apiato Repository is engineered for high performance, even at scale. Its intelligent caching and query optimization features are designed to make your APIs and applications fast, reliable, and cost-effective.

---

## 1. Why Caching Matters

- **Speed**: Reduce database load and response times for repeated queries.
- **Scalability**: Handle more users and requests with the same infrastructure.
- **Consistency**: Smart invalidation ensures users always see up-to-date data.

---

## 2. How Caching Works in Apiato Repository

- **Automatic caching**: All queries are cached by default (configurable duration).
- **Smart invalidation**: Cache is cleared automatically on create, update, or delete.
- **Manual control**: Skip or clear cache for specific queries or scenarios.
- **Cache tagging**: Use advanced cache drivers (like Redis) for granular control.

---

## 3. Real-World Usage Examples

**Default caching:**
```php
$users = $repo->all(); // Cached for 30 minutes by default
```

**Skip cache for a query:**
```php
$users = $repo->skipCache()->all();
```

**Clear cache after a bulk update:**
```php
$repo->updateWhere(['status' => 'inactive'], ['last_login' => null]);
$repo->clearCache();
```

---

## 4. Configuration & Tuning

**config/repository.php:**
```php
'cache' => [
    'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
    'minutes' => env('REPOSITORY_CACHE_MINUTES', 30),
    'clean' => [
        'enabled' => env('REPOSITORY_CACHE_CLEAN_ENABLED', true),
        'on' => [
            'create' => true,
            'update' => true,
            'delete' => true,
        ]
    ],
],
```

**.env:**
```env
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=30
REPOSITORY_CACHE_CLEAN_ENABLED=true
```

---

## 5. Performance Optimization Tips

- **Eager load relationships**: `$repo->with(['posts'])->all();`
- **Use criteria for reusable, optimized filters**.
- **Batch operations**: Update or delete many records in a single query.
- **Paginate or chunk large datasets**: Use `paginate()`, `cursorPaginate()`, or `chunk()` for memory efficiency.
- **Profile and tune**: Use Laravel Telescope, Xdebug, or Blackfire to find bottlenecks.

---

## 6. Best Practices

- **Use Redis or another taggable cache driver** for best results.
- **Clear cache after bulk operations** to avoid stale data.
- **Monitor cache hit rates** and tune durations for your workload.

---

**Next:**
- [Real-World Examples →](real-world-examples.md)
- [Reference: Configuration →](../reference/configuration.md)
