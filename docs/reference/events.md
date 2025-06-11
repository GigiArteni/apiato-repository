# Events Reference: Hook Into Every Action

Apiato Repository is event-driven at its core. Every major action fires an event, so you can hook in for logging, auditing, notifications, or custom business logic—without modifying the repository itself.

---

## 1. Core Repository Events

- `RepositoryCreated`
- `RepositoryCreating`
- `RepositoryUpdated`
- `RepositoryUpdating`
- `RepositoryDeleted`
- `RepositoryDeleting`
- `RepositoryBulkCreated`
- `RepositoryBulkUpdated`
- `RepositoryBulkDeleted`
- `RepositoryCriteriaApplied`
- `RepositorySanitizedEvent`

---

## 2. When Are Events Fired?

- **Creating**: Before and after a record is created
- **Updating**: Before and after a record is updated
- **Deleting**: Before and after a record is deleted
- **Bulk operations**: For batch create, update, delete
- **Criteria**: When criteria are applied to a query
- **Sanitization**: When input is sanitized before persistence

---

## 3. How to Listen for Events

**Example:**
```php
use Apiato\Repository\Events\RepositoryCreated;
use Illuminate\Support\Facades\Event;

Event::listen(RepositoryCreated::class, function($event) {
    $model = $event->getModel();
    $repository = $event->getRepository();
    // Log, notify, or audit
});
```

---

## 4. Real-World Use Cases

- **Audit logs**: Track who changed what, when, and how.
- **Notifications**: Send alerts on important changes.
- **Integrations**: Sync with external systems on data changes.
- **Custom business logic**: Enforce invariants, trigger workflows, or update related data.

---

## 5. Best Practices

- **Keep event listeners fast**—offload heavy work to jobs or queues.
- **Use events for cross-cutting concerns**—never pollute your repositories with logging or side effects.
- **Test your listeners**—ensure they handle all event payloads and edge cases.

---

**See also:** [API Methods](api-methods.md), [Advanced Features](../guides/advanced-features.md)
