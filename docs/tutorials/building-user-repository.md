# Tutorial: Building a User Repository (Full Feature Walkthrough)

This tutorial walks you through building a fully-featured `UserRepository` using Apiato Repository. You'll cover CRUD, HashId support, advanced search, criteria, caching, events, presenters, and more.

---

## 1. Generate the Repository and Model

```powershell
php artisan make:repository UserRepository --model=User
```

---

## 2. Define Searchable Fields

```php
// app/Repositories/UserRepository.php
class UserRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => '=',
        'role_id' => '=', // HashId support
        'company.name' => 'like', // Relationship search
    ];
}
```

---

## 3. Basic CRUD Operations

```php
$userRepo = app(UserRepository::class);
$user = $userRepo->create(['name' => 'Alice', 'email' => 'alice@example.com']);
$found = $userRepo->find($user->getKey());
$userRepo->update(['status' => 'active'], $user->getKey());
$userRepo->delete($user->getKey());
```

---

## 4. HashId Integration

- All repository methods accept HashIds for IDs and relationships.
- Example:
  ```php
  $user = $userRepo->find('gY6N8'); // HashId decoded automatically
  $users = $userRepo->findWhereIn('role_id', ['abc123', 'def456']);
  ```

---

## 5. Advanced Search & Filtering

- API Example:
  ```bash
  GET /api/users?search=name:alice;role_id:abc123&filter=status:active
  ```
- Eloquent Example:
  ```php
  $users = $userRepo->findWhere([
      ['status', '=', 'active'],
      ['role_id', 'in', ['abc123', 'def456']],
  ]);
  ```

---

## 6. Criteria & Scopes

- Create a custom criteria:
  ```php
  class ActiveUsersCriteria implements CriteriaInterface {
      public function apply($model, RepositoryInterface $repository) {
          return $model->where('status', 'active');
      }
  }
  $userRepo->pushCriteria(new ActiveUsersCriteria());
  ```
- Use `scopeQuery` for ad-hoc logic:
  ```php
  $userRepo->scopeQuery(fn($q) => $q->where('created_at', '>', now()->subMonth()))->all();
  ```

---

## 7. Caching & Performance

- All queries are cached by default.
- Skip or clear cache as needed:
  ```php
  $userRepo->skipCache()->all();
  $userRepo->clearCache();
  ```

---

## 8. Events & Listeners

- Listen for repository events:
  ```php
  Event::listen(RepositoryCreated::class, function($event) {
      // Handle event
  });
  ```

---

## 9. Presenters & Transformers

- Use a presenter for API output:
  ```php
  class UserPresenter extends FractalPresenter {
      public function getTransformer() { return new UserTransformer(); }
  }
  $userRepo->setPresenter(new UserPresenter());
  $users = $userRepo->paginate(); // Transformed output
  ```

---

## 10. Validation

- Use validators for create/update:
  ```php
  $userRepo->validator()->with(['name' => 'Test'])->passes();
  ```

---

## 11. Real-World Patterns

- Relationship queries:
  ```php
  $users = $userRepo->whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
  ```
- Batch operations:
  ```php
  $userRepo->updateWhere(['status' => 'inactive'], ['last_login' => null]);
  $userRepo->deleteWhere(['status' => 'spam']);
  ```

---

## 12. Best Practices

- Always use HashIds in APIs.
- Define all searchable fields.
- Use criteria for reusable business logic.
- Leverage caching and events for performance and integrations.

---

For more, see the [API Methods Reference](../reference/api-methods.md) and [Testing Repositories](testing-repositories.md).
