# Performance Optimization with Apiato Repository

This tutorial covers advanced performance optimization techniques using Apiato Repository, including intelligent caching, eager loading, batch operations, query tuning, and profiling.

---

## 1. Leveraging Intelligent Caching

- All queries are cached by default. Tune cache duration in `config/repository.php` or `.env`:
  ```env
  REPOSITORY_CACHE_ENABLED=true
  REPOSITORY_CACHE_MINUTES=30
  ```
- Manually skip or clear cache:
  ```php
  $repo->skipCache()->all();
  $repo->clearCache();
  ```

---

## 2. Eager Loading Relationships

- Always eager load related data for API endpoints:
  ```php
  $users = $repo->with(['roles', 'company'])->paginate();
  ```

---

## 3. Batch Operations for Efficiency

- Update or delete many records in a single query:
  ```php
  $repo->updateWhere(['status' => 'inactive'], ['last_login' => null]);
  $repo->deleteWhere(['status' => 'spam']);
  ```

---

## 4. Query Tuning and Scopes

- Use `scopeQuery` for custom, optimized queries:
  ```php
  $repo->scopeQuery(fn($q) => $q->where('created_at', '>', now()->subDays(30)))->all();
  ```
- Use criteria for reusable, optimized filters.

---

## 5. Paginate or Chunk Large Datasets

- Use `paginate()`, `cursorPaginate()`, or `chunk()` for memory efficiency:
  ```php
  $repo->chunk(1000, function($users) {
      foreach ($users as $user) {
          // Process user
      }
  });
  ```

---

## 6. Profiling and Monitoring

- Use Laravel Telescope, Xdebug, or Blackfire to profile queries and find bottlenecks.
- Monitor cache hit rates and tune durations for your workload.

---

## 7. Best Practices

- Use Redis or another taggable cache driver for best results.
- Always eager load relationships for APIs.
- Profile and tune regularly as your data grows.

---

For more, see the [Caching & Performance Guide](../guides/caching-performance.md) and [Troubleshooting](../reference/troubleshooting.md).
