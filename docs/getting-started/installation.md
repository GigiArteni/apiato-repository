# Installation Guide

Follow these steps to install Apiato Repository in your Apiato v.13 project.

---

## 1. Require the Package

```powershell
composer require apiato/repository
```

---

## 2. Publish the Configuration File

```powershell
php artisan vendor:publish --provider="Apiato\Repository\Providers\RepositoryServiceProvider" --tag=config
```

---

## 3. (Optional) Publish the Event Provider

```powershell
php artisan vendor:publish --provider="Apiato\Repository\Providers\EventServiceProvider"
```

---

## 4. Verify Installation

- Check that `config/repository.php` exists.
- Run `php artisan` and confirm new repository-related commands are available.

---

## 5. Next Steps

- Continue with the [Quick Start](quick-start.md) or [Getting Started](../guides/getting-started.md) guides.
- Review the [Configuration Reference](../reference/configuration.md) for tuning options.
