# Quick Start

This page provides a concise, step-by-step guide to get you up and running with Apiato Repository in minutes.

---

## 1. Install the Package

```powershell
composer require apiato/repository
```

---

## 2. Publish Configuration

```powershell
php artisan vendor:publish --provider="Apiato\Repository\Providers\RepositoryServiceProvider" --tag=config
```

---

## 3. Create Your First Repository

```powershell
php artisan make:repository UserRepository --model=User
```

---

## 4. Basic Usage Example

```php
$userRepo = app(UserRepository::class);
$user = $userRepo->create(['name' => 'John Doe', 'email' => 'john@example.com']);
$found = $userRepo->find($user->getKey());
```

---

## 5. Next Steps

- See [Getting Started](../guides/getting-started.md) for a full walkthrough.
- Explore [Basic Usage](../guides/basic-usage.md) and [Advanced Features](../guides/advanced-features.md).
- Review [Configuration Reference](../reference/configuration.md) for tuning and options.
