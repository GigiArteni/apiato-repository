# Getting Started with Apiato Repository

Welcome to the **Apiato Repository**! This guide will help you quickly set up, configure, and use the package in your Apiato v.13 project. Whether you're migrating from l5-repository or starting fresh, follow these steps for a smooth experience.

---

## 🚀 Installation

1. **Require the package via Composer:**

```powershell
composer require apiato/repository
```

2. **Publish the configuration file:**

```powershell
php artisan vendor:publish --provider="Apiato\Repository\Providers\RepositoryServiceProvider" --tag=config
```

3. **(Optional) Publish the event provider:**

```powershell
php artisan vendor:publish --provider="Apiato\Repository\Providers\EventServiceProvider"
```

---

## ⚙️ Basic Configuration

- The main configuration file is at `config/repository.php`.
- HashId integration, caching, enhanced search, and other features can be enabled/disabled via this file or environment variables.
- Example environment variables:

```env
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_HASHIDS_ENABLED=true
REPOSITORY_ENHANCED_SEARCH=true
```

---

## 🏗️ Creating Your First Repository

1. **Generate a repository and model:**

```powershell
php artisan make:repository UserRepository --model=User
```

2. **Define searchable fields in your repository:**

```php
// app/Repositories/UserRepository.php
class UserRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => 'like',
        'status' => '=',
    ];
}
```

3. **Use the repository in your controller or service:**

```php
$user = app(UserRepository::class)->find('gY6N8'); // HashId decoded automatically
```

---

## 🔍 Basic Usage Examples

- **Create:**
  ```php
  $user = $repository->create(['name' => 'John Doe', 'email' => 'john@example.com']);
  ```
- **Find:**
  ```php
  $user = $repository->find('gY6N8');
  ```
- **Update:**
  ```php
  $user = $repository->update(['name' => 'Jane Doe'], 'gY6N8');
  ```
- **Delete:**
  ```php
  $repository->delete('gY6N8');
  ```

---

## 🧠 Next Steps

- Explore [Basic Usage](basic-usage.md) for more examples.
- See [Configuration Reference](../reference/configuration.md) for advanced settings.
- Check [Troubleshooting](../reference/troubleshooting.md) if you encounter issues.

---

**Apiato Repository** makes advanced repository patterns, HashId integration, and high performance easy for Apiato projects. For more, see the full documentation in the `docs/` folder.
