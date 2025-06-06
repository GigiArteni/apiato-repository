# Advanced Features: Unlocking the Full Power of Apiato Repository

Apiato Repository is more than just CRUD. It’s a toolkit for building scalable, maintainable, and high-performance data access layers—ready for the most demanding business requirements.

---

## 1. Batch Operations: Work with Many Records Efficiently

**Why?**
- Update, delete, or fetch many records in a single, optimized query.
- Essential for admin panels, bulk actions, and data migrations.

**Examples:**
```php
// Find multiple users by HashIds
$users = $repo->findWhereIn('id', ['abc123', 'def456']);

// Bulk update
$repo->updateWhere(['status' => 'inactive'], ['last_login' => null]);

// Bulk delete
$repo->deleteWhere(['status' => 'spam']);
```

---

## 2. Relationship Queries: Real-World Data Traversal

**Why?**
- Fetch users with specific posts, roles, or nested relationships—using HashIds everywhere.

**Examples:**
```php
// Eager load relationships
$users = $repo->with(['posts', 'roles'])->paginate();

// Filter by related data
$users = $repo->whereHas('posts', fn($q) => $q->where('published', true))->get();

// Nested relationships
$users = $repo->whereHas('company.projects', fn($q) => $q->where('status', 'active'))->get();
```

---

## 3. Scopes: Custom Query Logic On-the-Fly

**Why?**
- Apply ad-hoc query logic without polluting your repository or model.

**Example:**
```php
$repo->scopeQuery(fn($q) => $q->where('created_at', '>', now()->subDays(30)))->all();
```

---

## 4. Field Visibility: Control What Data is Returned

**Why?**
- Hide sensitive fields or limit output for APIs.

**Examples:**
```php
$repo->hidden(['password', 'remember_token'])->all();
$repo->visible(['id', 'name', 'email'])->all();
```

---

## 5. Custom Criteria: Encapsulate Business Logic

**Why?**
- Reuse complex filters, permissions, or business rules across your app.

**Example:**
```php
class ActiveUsersCriteria implements CriteriaInterface {
    public function apply($model, RepositoryInterface $repository) {
        return $model->where('status', 'active')->whereNotNull('email_verified_at');
    }
}
$repo->pushCriteria(new ActiveUsersCriteria());
```

---

## 6. Event System: React to Repository Changes

**Why?**
- Trigger actions, logging, or notifications on create, update, or delete.

**Example:**
```php
Event::listen(RepositoryEntityCreated::class, function($event) {
    // Log, notify, or audit
});
```

---

## 7. Caching: Performance at Scale

**Why?**
- All queries are cached by default. Manual control for advanced scenarios.

**Examples:**
```php
$repo->skipCache()->all(); // Bypass cache
$repo->clearCache(); // Clear cache for this repository
```

---

## 8. Best Practices

- Use batch operations for admin/bulk actions.
- Always eager load relationships for API endpoints.
- Use criteria for reusable, testable business logic.
- Leverage events for audit, notifications, and integrations.
- Control field visibility for security and performance.

---

**Next:**
- [HashId Integration →](hashid-integration.md)
- [Enhanced Search →](enhanced-search.md)
- [Real-World Examples →](real-world-examples.md)
