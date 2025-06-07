# Testing Repositories: Best Practices & Real-World Examples

Testing your repositories is essential for ensuring data integrity, business logic correctness, and long-term maintainability. This tutorial covers unit and integration testing for Apiato Repository, including HashId support, criteria, caching, and event-driven logic.

---

## 1. Unit Testing a Repository

- Use PHPUnit and Laravel's built-in testing tools.
- Mock dependencies (e.g., models, cache) for isolated tests.

**Example:**
```php
use Tests\Unit\BaseRepositoryTest;
use App\Repositories\UserRepository;

public function test_create_user()
{
    $repo = app(UserRepository::class);
    $user = $repo->create(['name' => 'Test', 'email' => 'test@example.com']);
    $this->assertNotNull($user->id);
    $this->assertEquals('Test', $user->name);
}
```

---

## 2. Integration Testing with HashIds

- Test that all repository methods accept and decode HashIds.
- Use factories to create test data.

**Example:**
```php
public function test_find_by_hashid()
{
    $repo = app(UserRepository::class);
    $user = User::factory()->create();
    $hashid = $user->getHashId();
    $found = $repo->find($hashid);
    $this->assertEquals($user->id, $found->id);
}
```

---

## 3. Testing Criteria and Scopes

- Write tests for custom criteria and scopeQuery logic.

**Example:**
```php
public function test_active_users_criteria()
{
    $repo = app(UserRepository::class);
    $repo->pushCriteria(new ActiveUsersCriteria());
    $users = $repo->all();
    $this->assertTrue($users->every(fn($u) => $u->status === 'active'));
}
```

---

## 4. Testing Caching Behavior

- Test that queries are cached and invalidated as expected.

**Example:**
```php
public function test_cache_invalidation_on_update()
{
    $repo = app(UserRepository::class);
    $user = $repo->create(['name' => 'CacheTest', 'email' => 'cache@example.com']);
    $repo->find($user->id); // Prime cache
    $repo->update(['name' => 'Updated'], $user->id);
    $updated = $repo->find($user->id);
    $this->assertEquals('Updated', $updated->name);
}
```

---

## 5. Testing Events

- Assert that repository events are fired and listeners are triggered.

**Example:**
```php
public function test_entity_created_event_fired()
{
    Event::fake();
    $repo = app(UserRepository::class);
    $repo->create(['name' => 'EventTest', 'email' => 'event@example.com']);
    Event::assertDispatched(\Apiato\Repository\Events\RepositoryEntityCreated::class);
}
```

---

## 6. Best Practices

- Use factories and seeders for test data.
- Isolate unit tests; use the database for integration tests.
- Test all repository features: CRUD, HashIds, criteria, caching, events, and relationships.
- Run tests with `composer test` before every pull request.

---

For more, see the [Testing Guide](../contributing/testing-guide.md) and real test examples in `tests/Unit/` and `tests/Feature/`.
