# Migration from l5-repository

This guide helps you migrate your project from `prettus/l5-repository` (or `andersao/l5-repository`) to the new `apiato/repository` package with minimal friction.

---

## 1. Replace the Package

- Remove the old package:
  ```powershell
  composer remove prettus/l5-repository andersao/l5-repository
  ```
- Install Apiato Repository:
  ```powershell
  composer require apiato/repository
  ```

---

## 2. Update Namespaces & Imports

- Change all `Prettus\Repository` references to `Apiato\Repository` in your codebase:
  - Repositories
  - Criteria
  - Presenters
  - Contracts
  - Traits

---

## 3. Publish and Review Configuration

- Publish the new config file:
  ```powershell
  php artisan vendor:publish --provider="Apiato\Repository\Providers\RepositoryServiceProvider" --tag=config
  ```
- Review and update `config/repository.php` for new options (HashId, enhanced search, caching, etc).

---

## 4. Update Custom Criteria, Presenters, and Validators

- Update namespaces and method signatures as needed.
- Review for new features (HashId support, enhanced search, etc).

---

## 5. Test Your Application

- Run your test suite and check for any breaking changes.
- Pay special attention to:
  - HashId decoding in all repository methods
  - Enhanced search and filtering
  - Caching and event-driven logic

---

## 6. Troubleshooting

- See [Troubleshooting](../reference/troubleshooting.md) for common migration issues and solutions.

---

**Tip:** The new package is a drop-in replacement for most use cases, but always review your custom logic and test thoroughly after migration.
