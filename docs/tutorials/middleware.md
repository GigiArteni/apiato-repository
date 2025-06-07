# Middleware & Cross-Cutting Concerns Tutorial

This tutorial covers the industry-first repository middleware system in Apiato Repository. Learn how to apply, configure, and extend middleware for audit, caching, rate-limiting, tenant-scope, performance monitoring, and custom logic.

---

## 1. What is Repository Middleware?

- Middleware allows you to apply cross-cutting concerns (like logging, caching, rate-limiting) to repository operations, just like HTTP middleware in Laravel.
- Middleware can be applied globally, per-repository, or per-operation.

---

## 2. Applying Middleware Globally

- In `config/repository.php`:
  ```php
  'middleware' => [
      'default_stack' => ['audit', 'cache:30'],
      'available' => [
          'audit' => \Apiato\Repository\Middleware\AuditMiddleware::class,
          'cache' => \Apiato\Repository\Middleware\CacheMiddleware::class,
          'rate-limit' => \Apiato\Repository\Middleware\RateLimitMiddleware::class,
          'tenant-scope' => \Apiato\Repository\Middleware\TenantScopeMiddleware::class,
          'performance' => \Apiato\Repository\Middleware\PerformanceMonitorMiddleware::class,
      ]
  ],
  ```

---

## 3. Per-Repository Middleware

- In your repository class:
  ```php
  protected $middleware = [
      'audit:create,update,delete',
      'cache:45',
      'tenant-scope:company_id',
      'rate-limit:200,1',
  ];
  ```

---

## 4. Per-Operation Middleware

- Apply middleware to a single operation:
  ```php
  $user = $repository->middleware(['audit', 'performance:100'])->update($data, $id);
  ```

---

## 5. Available Middleware & Examples

- **Audit**: Logs all changes, user, IP, operation, duration.
- **Cache**: Advanced caching with tags, auto-invalidation.
- **Rate-Limit**: Prevents abuse, per-user/IP.
- **Tenant-Scope**: Multi-tenancy, auto-filters by tenant.
- **Performance**: Monitors query time, alerts on slow ops.

---

## 6. Creating Custom Middleware

- Implement `RepositoryMiddlewareInterface` and register in config.
- Example:
  ```php
  class CustomLoggerMiddleware implements RepositoryMiddlewareInterface {
      public function handle($operation, $next) {
          logger('Before: ' . $operation);
          $result = $next($operation);
          logger('After: ' . $operation);
          return $result;
      }
  }
  ```

---

## 7. Best Practices

- Use middleware for all cross-cutting concerns (logging, audit, caching, etc).
- Keep middleware fast; offload heavy work to jobs/queues.
- Document custom middleware for your team.

---

For more, see the [API Methods Reference](../reference/api-methods.md#middleware-system) and [Advanced Features](../guides/advanced-features.md).
