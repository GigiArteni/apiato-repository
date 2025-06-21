# Laravel Integration Guide

This guide explains how to use Apiato Repository in standard Laravel projects, what features are available, and how to get the most out of the package outside of Apiato.

---

## 1. Compatibility

- **Laravel Support**: Works with Laravel 11+ and 12+.
- **PHP Support**: Requires PHP 8.1 or higher.
- **No Apiato Required**: You can use all core repository features in any Laravel project.

---

## 2. Installation & Setup

- Install via Composer:
  ```powershell
  composer require apiato/repository
  ```
- Publish the config file:
  ```powershell
  php artisan vendor:publish --provider="Apiato\Repository\Providers\RepositoryServiceProvider" --tag=config
  ```
- (Optional) Publish the event provider for event-driven features.

---

## 3. Available Features in Laravel

- **Repository Pattern**: All CRUD, query, and batch methods.
- **Criteria & Scopes**: Reusable, stackable query logic.
- **Enhanced Search**: Boolean, fuzzy, relevance, and relationship search.
- **Caching**: Intelligent, taggable, auto-invalidation.
- **Bulk Operations**: High-performance insert/update/upsert/delete.
- **Middleware**: Audit, cache, rate-limit, tenant-scope, performance.
- **Validation**: Laravel-style, custom rules.
- **Events**: Full CRUD/bulk lifecycle events.
- **Presenters & Transformers**: Fractal integration for API output.

---

## 4. Apiato-Specific Features (Not Available in Plain Laravel)

- **HashId auto-integration**: You must manually decode/encode HashIds if not using Apiato.
- **Container/Ship structure**: Use standard Laravel folder structure.

---

## 5. Best Practices

- Use repositories for all data access; avoid direct model queries in controllers.
- Use criteria for reusable business logic.
- Leverage cross-cutting concerns for audit, caching, and performance.
- Use Laravel's validation and event system for security and integrations.

---

For more, see the [Feature Matrix](feature-matrix.md), [API Methods Reference](reference/api-methods.md), and [Migration Guide](getting-started/migration-from-l5.md).
