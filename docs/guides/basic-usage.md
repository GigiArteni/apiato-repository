# Basic Usage: From Zero to Productive

Apiato Repository is designed to be intuitive for both Laravel and Apiato users, but it also unlocks advanced power when you need it. This guide covers not just the "how" but the "why"—so you can confidently build robust, maintainable, and high-performance data access layers.

---

## 1. What is a Repository (and Why Use One)?

A repository abstracts your data access logic, so your controllers, services, and business logic never need to know about Eloquent, SQL, or even which database you use. This means:
- **Cleaner code**: No more database logic in controllers.
- **Easier testing**: Swap implementations, mock, or stub with ease.
- **Consistency**: All data access follows the same rules, validation, and caching.

Apiato Repository takes this further with:
- **HashId support everywhere** (no more leaking database IDs)
- **Advanced search and filtering** (for real-world business needs)
- **Intelligent caching** (for performance at scale)

---

## 2. Creating Your First Repository

**Artisan Command:**
```powershell
php artisan make:repository UserRepository --model=User
```

This generates a repository class in the right namespace for your project (Laravel or Apiato). It will look like this:

```php
namespace App\Repositories; // or App\Containers\User\Data\Repositories for Apiato

use Apiato\Repository\Eloquent\BaseRepository;

class UserRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'id' => '=', // HashId support is automatic
        'role_id' => '=', // HashId support
        'company.name' => 'like', // Relationship search
    ];
}
```

---

## 3. CRUD Operations—With HashId Magic

All repository methods work with either integer IDs or HashIds. This means you can safely expose IDs in your API without leaking database internals.

```php
$userRepo = app(UserRepository::class);

// Create
$user = $userRepo->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Find (HashId or integer)
$user = $userRepo->find('gY6N8'); // HashId decoded automatically

// Update
$user = $userRepo->update(['name' => 'Jane Doe'], 'gY6N8');

// Delete
$userRepo->delete('gY6N8');
```

**Why does this matter?**
- You can use HashIds in URLs, API requests, and relationships—no extra code needed.
- All security and validation is handled for you.

---

## 4. Real-World Search & Filtering

Apiato Repository supports both simple and advanced search patterns, including multi-field, relationship, and boolean logic.

**API Example:**
```bash
GET /api/users?search=name:john;role_id:abc123&searchFields=name:like;role_id:=
```

**Eloquent Example:**
```php
$users = $userRepo->findWhere([
    ['status', '=', 'active'],
    ['role_id', 'in', ['abc123', 'def456']],
]);
```

**Relationship Search:**
```php
$users = $userRepo->whereHas('roles', function($q) {
    $q->where('name', 'admin');
})->get();
```

---

## 5. Advanced: Criteria, Scopes, and Caching

Apiato Repository is designed for real-world complexity. As your application grows, you’ll need to encapsulate business logic, optimize queries, and keep your APIs fast. Here’s how:

- **Criteria**: Encapsulate complex, reusable business logic as classes. For example, filter only active users, users by department, or high-value customers. Criteria can be stacked, parameterized, and reused across repositories. See [Advanced Features](advanced-features.md) for deep dives and real-world patterns.

- **Scopes**: Apply custom query logic on the fly, without polluting your repository or model. Scopes are perfect for ad-hoc filters, reporting, or analytics.

- **Caching**: All queries are cached by default for blazing speed. Apiato Repository uses intelligent, event-driven cache invalidation—so your data is always fresh. For special cases, use `$repo->skipCache()` to bypass cache, or `$repo->clearCache()` to force a refresh.

---

## 6. Best Practices

- **Always use HashIds in your APIs**—it’s automatic, secure, and future-proof.
- **Define all searchable fields in `$fieldSearchable`**—including relationships and IDs.
- **Use criteria for reusable business logic**—keep your code DRY, testable, and maintainable.
- **Leverage caching**—for high-traffic APIs, this is a game changer. Use Redis or a taggable cache driver for best results.
- **Paginate or chunk large datasets**—never load thousands of records at once.
- **Profile and tune**—use Laravel Telescope, Xdebug, or Blackfire to find and fix bottlenecks.

---

## 7. Next Steps

- [Advanced Features →](advanced-features.md)
- [HashId Integration →](hashid-integration.md)
- [Enhanced Search →](enhanced-search.md)
- [Real-World Examples →](real-world-examples.md)

---

**Tip:** For a deep dive into search, filtering, and real-world business scenarios, see the [Enhanced Search](enhanced-search.md) and [Real-World Examples](real-world-examples.md) guides. For advanced caching, batch operations, and event-driven logic, see [Advanced Features](advanced-features.md).
