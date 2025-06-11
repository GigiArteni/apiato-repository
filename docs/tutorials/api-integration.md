# Real-World API & Controller Integration Tutorial

This tutorial demonstrates how to integrate Apiato Repository into real-world API controllers and services, leveraging all advanced features: criteria, middleware, bulk ops, search, sanitization, transactions, and events.

---

## 1. Basic API Controller Example

```php
class UserController
{
    protected UserRepository $userRepository;

    public function store(Request $request)
    {
        // Middleware, sanitization, and HashIds handled automatically
        $user = $this->userRepository->create($request->all());
        return response()->json(['user' => $user]);
    }
}
```

---

## 2. Bulk Operations Endpoint

```php
public function bulkStore(Request $request)
{
    $stats = $this->userRepository->bulkUpsert(
        $request->users,
        ['id'],
        ['name', 'email', 'updated_at']
    );
    return response()->json($stats);
}
```

---

## 3. Advanced Search & Filtering

```php
public function search(Request $request)
{
    $users = $this->userRepository
        ->pushCriteria(app(RequestCriteria::class))
        ->with(['roles', 'company'])
        ->paginate(20);
    return response()->json($users);
}
```

---

## 4. Transactional Operations

```php
public function createUserWithProfile(Request $request)
{
    $result = $this->userRepository->transaction(function() use ($request) {
        $user = $this->userRepository->create($request->user);
        $profile = $this->profileRepository->create($request->profile);
        return ['user' => $user, 'profile' => $profile];
    });
    return response()->json($result);
}
```

---

## 5. Event-Driven Integrations

```php
Event::listen(RepositoryCreated::class, function($event) {
    NotificationService::send($event->getModel());
});
```

---

## 6. Best Practices

- Always use criteria for API query parsing.
- Use middleware for audit, caching, and security.
- Wrap complex operations in transactions.
- Listen for events to trigger notifications, logging, or integrations.

---

For more, see the [Real-World Examples](../guides/real-world-examples.md), [API Methods Reference](../reference/api-methods.md), and [Testing Repositories](testing-repositories.md).
