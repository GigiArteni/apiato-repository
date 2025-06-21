# Performance Tips for Apiato Repository

## 1. Use Eager Loading
Always use Eloquent's `with()` method to eager load relationships and avoid N+1 query problems.

```php
$users = $repository->with(['profile', 'roles'])->get();
```

## 2. Optimize Bulk Operations
For large datasets, use the provided `bulkInsert`, `bulkUpdate`, and `bulkUpsert` methods. Adjust `batch_size` for your server's memory and DB performance.

```php
$repository->bulkInsert($records, ['batch_size' => 2000]);
```

## 3. Use Caching for Expensive Queries
Enable and configure repository caching for queries that are expensive or frequently repeated.

## 4. Use Transactions for Data Integrity
Wrap critical or multi-step operations in transactions to ensure data consistency.

## 5. Profile and Monitor
Use Laravel Telescope, Debugbar, or custom logging to monitor query performance and optimize as needed.

## 6. Use Queues for Heavy Imports
For very large imports, consider dispatching jobs to a queue to avoid timeouts and improve user experience.
