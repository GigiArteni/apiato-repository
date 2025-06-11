# Apiato Repository: Exhaustive API Reference

This document provides a **comprehensive, professional, and user-centric reference** for all features, methods, traits, contracts, middleware, events, and advanced capabilities of the Apiato Repository. It is designed to serve as your single source of truth for every aspect of repository usage, configuration, and extension.

---

## Table of Contents
1. [Core CRUD & Query Methods](#core-crud--query-methods)
2. [Advanced Querying & Criteria](#advanced-querying--criteria)
3. [Bulk Operations](#bulk-operations)
4. [Sanitization & Security](#sanitization--security)
5. [Middleware System](#middleware-system)
6. [Caching & Performance](#caching--performance)
7. [Transactions & Error Handling](#transactions--error-handling)
8. [Presenters & Transformers](#presenters--transformers)
9. [Validation](#validation)
10. [Events & Event Payloads](#events--event-payloads)
11. [Contracts & Traits](#contracts--traits)
12. [Advanced Search & HashId Integration](#advanced-search--hashid-integration)
13. [Real-World Usage Patterns](#real-world-usage-patterns)
14. [Performance Tips](#performance-tips)

---

## Core CRUD & Query Methods

All core methods support **HashId decoding** and are middleware/criteria/transaction/caching aware.

| Method | Signature | Description | Example |
|--------|-----------|-------------|---------|
| all | all($columns = ['*']) | Get all records | $repo->all(['id','name']) |
| first | first($columns = ['*']) | Get first record | $repo->first() |
| find | find($id, $columns = ['*']) | Find by ID or HashId | $repo->find('abc123') |
| findByField | findByField($field, $value, $columns = ['*']) | Find by field value | $repo->findByField('email','a@b.com') |
| findWhere | findWhere(array $where, $columns = ['*']) | Find by multiple conditions | $repo->findWhere(['status'=>'active']) |
| findWhereIn | findWhereIn($field, array $values, $columns = ['*']) | Field in array (HashIds ok) | $repo->findWhereIn('id',['abc','def']) |
| findWhereNotIn | findWhereNotIn($field, array $values, $columns = ['*']) | Field not in array | $repo->findWhereNotIn('role_id',['x','y']) |
| findWhereBetween | findWhereBetween($field, array $range, $columns = ['*']) | Field between values | $repo->findWhereBetween('age',[18,30]) |
| create | create(array $attributes) | Create new record | $repo->create(['name'=>'A']) |
| update | update(array $attributes, $id) | Update by ID/HashId | $repo->update(['name'=>'B'],'abc123') |
| updateOrCreate | updateOrCreate(array $attributes, array $values=[]) | Update or create | $repo->updateOrCreate(['email'=>'a@b.com'],['name'=>'A']) |
| delete | delete($id) | Delete by ID/HashId | $repo->delete('abc123') |
| deleteWhere | deleteWhere(array $where) | Delete by conditions | $repo->deleteWhere(['status'=>'inactive']) |
| orderBy | orderBy($column, $direction='asc') | Order results | $repo->orderBy('created_at','desc') |
| with | with(array $relations) | Eager load relations | $repo->with(['posts','roles']) |
| has | has(string $relation) | Filter by relation existence | $repo->has('posts') |
| whereHas | whereHas(string $relation, Closure $cb) | Filter by related data | $repo->whereHas('roles',fn($q)=>$q->where('name','admin')) |
| hidden | hidden(array $fields) | Hide fields | $repo->hidden(['password']) |
| visible | visible(array $fields) | Show only fields | $repo->visible(['id','name']) |
| scopeQuery | scopeQuery(Closure $cb) | Custom query scope | $repo->scopeQuery(fn($q)=>$q->where('active',1)) |

---

## Advanced Querying & Criteria

- **Criteria**: Encapsulate reusable query logic. Implement `CriteriaInterface` and push to the repository.
- **RequestCriteria**: Parses API query params (`search`, `filter`, `orderBy`, etc.) and supports enhanced search.
- **Multi-Criteria**: Chain multiple criteria for complex filtering.

**Example:**
```php
$repo->pushCriteria(new ActiveUsersCriteria())->pushCriteria(new RecentActivityCriteria(7))->all();
```

---

## Bulk Operations

Optimized for performance, with full HashId and event support.

| Method | Description | Example |
|--------|-------------|---------|
| updateWhere | Update all matching | $repo->updateWhere(['status'=>'pending'],['status'=>'active']) |
| deleteWhere | Delete all matching | $repo->deleteWhere(['status'=>'spam']) |
| updateWhereIn | Update where field in array | $repo->updateWhereIn('id',['abc','def'],['active'=>1]) |
| deleteWhereIn | Delete where field in array | $repo->deleteWhereIn('id',['abc','def']) |
| bulkCreate | Create many at once | $repo->bulkCreate([...]) |
| bulkUpdate | Update many by key | $repo->bulkUpdate([...],'id') |
| bulkInsert | Insert many, auto-timestamps | $repo->bulkInsert([...]) |
| bulkUpsert | Insert or update many | $repo->bulkUpsert([...],['email']) |

**Events:** All bulk ops fire events (see [Events & Event Payloads](#events--event-payloads)).

---

## Sanitization & Security

- **Automatic**: All create/update ops pass through sanitization (integrates with Apiato's `sanitizeInput()` if available).
- **Custom Rules**: Use `setSanitizationRules()` for field-specific logic.
- **Skip/Force**: Use `skipSanitization()` to bypass for trusted data.
- **Audit**: Fires `RepositorySanitizedEvent` with before/after data and changed fields.

**Example:**
```php
$repo->setSanitizationRules(['email'=>'email','bio'=>'html_purify'])->create($data);
$repo->skipSanitization()->update($data,'abc123');
```

---

## Middleware System

Apply cross-cutting logic to all repository methods. Built-in middleware:
- **AuditMiddleware**: Logs all create/update/delete with user, IP, duration.
- **CacheMiddleware**: Advanced caching with tags, auto-invalidation.
- **TenantScopeMiddleware**: Multi-tenant data isolation.
- **RateLimitMiddleware**: Prevents abuse by limiting method calls.
- **PerformanceMonitorMiddleware**: Logs slow queries and query counts.

**Usage:**
```php
protected $middleware = [
    \Apiato\Repository\Middleware\AuditMiddleware::class,
    'cache:60', // 60 minutes
    'tenant-scope:company_id,123',
];
```
Custom middleware: extend `RepositoryMiddleware` and register.

---

## Caching & Performance

- **remember($minutes)**: Cache next query for N minutes.
- **skipCache()**: Bypass cache for next query.
- **clearCache()**: Flush all cache for this repo.
- **setCacheRepository()**: Use custom cache backend.
- **Cache Middleware**: For global, tagged, or per-method caching.

**Example:**
```php
$repo->remember(30)->all();
$repo->skipCache()->find('abc123');
```

---

## Transactions & Error Handling

- **withTransaction()**: Force next op in a transaction.
- **skipTransaction()**: Bypass transaction for next op.
- **withIsolationLevel()**: Set isolation level (e.g., SERIALIZABLE).
- **transaction(callable $cb, $attempts = 3)**: Run callback in transaction with deadlock retry.
- **Deadlock/Timeout Handling**: Retries on common DB errors.

**Example:**
```php
$repo->withTransaction()->create($data);
$repo->transaction(function() use ($repo, $data) {
    $repo->create($data);
    // ...
});
```

---

## Presenters & Transformers

- **Presenters**: Format output for API/UI. Set with `setPresenter()` or via contract.
- **Transformers**: Transform data for API responses. Implement `TransformerInterface`.
- **skipPresenter()**: Bypass presenter for raw data.

**Example:**
```php
$repo->setPresenter(new UserPresenter())->all();
$repo->skipPresenter()->find('abc123');
```

---

## Validation

- **Validators**: Validate data before create/update. Set with `setValidator()` or via contract.
- **passesCreate()/passesUpdate()**: Validate for specific actions.
- **errors()**: Get validation errors.

**Example:**
```php
$repo->setValidator(new UserValidator())->create($data);
```

---

## Events & Event Payloads

All actions fire events for extensibility and audit:
- **RepositoryCreated/Updated/Deleted**: On single record ops.
- **RepositoryBulkCreated/Updated/Deleted**: On bulk ops.
- **RepositorySanitizedEvent**: On data sanitization.
- **RepositoryCriteriaApplied**: When criteria are applied.

**Payloads**: All events provide access to the repository, model(s), action, and context (see `src/Apiato/Repository/Events/`).

---

## Contracts & Traits

**Key Contracts:**
- `RepositoryInterface`: All core methods (CRUD, query, etc.)
- `CriteriaInterface`: For custom query logic
- `RepositoryCriteriaInterface`: Criteria management
- `PresenterInterface`: Data presentation
- `TransformerInterface`: Data transformation
- `ValidatorInterface`: Data validation
- `CacheableInterface`: Caching
- `Presentable`: For presentable objects

**Key Traits:**
- `SanitizableRepository`: Input sanitization
- `BulkOperations`: High-performance bulk ops
- `HasMiddleware`: Middleware system
- `CacheableRepository`: Caching
- `TransactionalRepository`: Transaction handling
- `PresentableTrait`: Presentation logic

---

## Advanced Search & HashId Integration

- **Enhanced Search**: Supports boolean, fuzzy, phrase, multi-field, and relationship search. See [Enhanced Search Guide](../guides/enhanced-search.md).
- **HashId Integration**: All ID fields auto-decode HashIds (find, search, filter, bulk ops, etc.).
- **RequestCriteria**: Parses API params for search/filter/order/with.

**API Example:**
```bash
GET /api/users?search=+developer +"react native" -intern&filter=company_id:abc123&orderBy=relevance_score&sortedBy=desc
```

---

## Real-World Usage Patterns

- **Complex Filtering:**
```php
$repo->pushCriteria(new SecurityAccessCriteria($userId, ['read'], 'project_123'))->with(['roles.permissions'])->all();
```
- **Batch Updates:**
```php
$repo->updateWhere(['company_id'=>'abc123'], ['status'=>'active']);
```
- **Advanced Search:**
```bash
GET /api/users?search="senior developer" +remote -contractor&filter=status:active
```
- **Transactional Bulk Ops:**
```php
$repo->withTransaction()->bulkUpdate([...], ['status'=>'pending']);
```

---

## Performance Tips
- Use `remember()` and `CacheMiddleware` for expensive queries.
- Chain criteria for reusable, composable filters.
- Use bulk ops for large data changes.
- Leverage enhanced search for user-facing APIs.
- Use transactions for critical or multi-step changes.

---

# API Methods Reference

## Cross-References

- For real-world usage, see [Real-World Examples](../guides/real-world-examples.md) and [Building a User Repository](../tutorials/building-user-repository.md).
- For advanced search, see [Enhanced Search Guide](../guides/enhanced-search.md) and [Implementing Search Tutorial](../tutorials/implementing-search.md).
- For middleware, see [Middleware Guide](../tutorials/middleware.md).
- For bulk operations and transactions, see [Bulk Operations Tutorial](../tutorials/bulk-operations.md).
- For testing, see [Testing Repositories](../tutorials/testing-repositories.md).
- For troubleshooting, see [Troubleshooting Reference](troubleshooting.md).

---

**Tip:** Use this reference as your single source of truth for all repository methods, then follow the links above for deep dives and real-world patterns.

---

**For more, see:**
- [Guides](../guides/)
- [Advanced Features](../guides/advanced-features.md)
- [Events Reference](events.md)
- [Configuration Reference](configuration.md)
- [Real-World Examples](../guides/real-world-examples.md)
