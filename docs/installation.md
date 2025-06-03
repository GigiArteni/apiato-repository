# Installation Guide

Complete installation guide for Apiato Repository package.

## System Requirements

- PHP 8.1 or higher
- Laravel 11.0+ or 12.0+
- Composer 2.0+

## Installation Steps

### 1. Install via Composer

```bash
composer require apiato/repository
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=repository-config
```

This will create `config/repository.php` with all configuration options.

### 3. Environment Configuration

Add these variables to your `.env` file:

```env
# Repository Cache Settings
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=60
REPOSITORY_CACHE_STORE=redis
REPOSITORY_CACHE_CLEAR_ON_WRITE=true

# HashId Settings (for Apiato integration)
HASHID_ENABLED=true
APIATO_ENABLED=true
```

## Apiato Integration

### For Existing Apiato Projects

If you're upgrading from `l5-repository`, the package is designed as a drop-in replacement:

```bash
# Remove old package
composer remove prettus/l5-repository

# Install new package
composer require apiato/repository
```

### Directory Structure

The package follows Apiato's Porto SAP architecture:

```
app/
├── Containers/
│   └── User/
│       └── Data/
│           ├── Repositories/
│           │   ├── UserRepository.php
│           │   └── UserRepositoryInterface.php
│           ├── Criteria/
│           │   └── ActiveUsersCriteria.php
│           └── Validators/
│               └── UserValidator.php
├── Ship/
│   └── Parents/
│       ├── Models/
│       ├── Repositories/
│       └── Criteria/
```

## Configuration Overview

Key configuration sections in `config/repository.php`:

### Generator Settings

```php
'generator' => [
    'basePath' => app_path(),
    'rootNamespace' => 'App\\',
    'paths' => [
        'models' => 'Ship/Parents/Models',
        'repositories' => 'Containers/{container}/Data/Repositories',
        'criteria' => 'Containers/{container}/Data/Criteria',
        'presenters' => 'Containers/{container}/UI/API/Transformers',
    ],
],
```

### Cache Configuration

```php
'cache' => [
    'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
    'minutes' => env('REPOSITORY_CACHE_MINUTES', 60),
    'store' => env('REPOSITORY_CACHE_STORE', 'default'),
    'clear_on_write' => env('REPOSITORY_CACHE_CLEAR_ON_WRITE', true),
],
```

### HashId Integration

```php
'hashid' => [
    'enabled' => env('HASHID_ENABLED', true),
    'auto_detect' => true,
    'auto_encode' => true,
    'fields' => ['id', '*_id'],
],
```

## Verification

Test your installation:

```bash
# Generate a test repository
php artisan make:repository TestRepository --model=User

# Clear cache
php artisan repository:clear-cache

# Run tests (if available)
php artisan test
```

## Laravel Service Container

The package automatically registers with Laravel's service container. You can inject repositories into your controllers:

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function index()
    {
        return $this->userRepository->paginate();
    }
}
```

## Troubleshooting

### Common Issues

**1. Class not found errors**
```bash
composer dump-autoload
```

**2. Cache issues**
```bash
php artisan config:clear
php artisan cache:clear
php artisan repository:clear-cache
```

**3. HashId integration not working**
```bash
# Ensure hashids/hashids is installed
composer require hashids/hashids

# Check Apiato HashId configuration
php artisan config:show apiato.hash-id
```

**4. Fractal serialization issues**
```bash
# Ensure league/fractal is installed
composer require league/fractal
```

## Next Steps

- [Repository Usage](repositories.md) - Learn basic repository operations
- [Criteria System](criteria.md) - Advanced query building
- [Caching Strategy](caching.md) - Optimize performance
- [HashId Integration](hashids.md) - Secure ID handling
- [Fractal Presenters](presenters.md) - Data transformation
- [Testing Guide](testing.md) - Test your repositories

## Support

- **Issues**: [GitHub Issues](https://github.com/apiato/repository/issues)
- **Documentation**: [Full Documentation](https://apiato.io/docs/components/repository)
- **Community**: [Apiato Discord](https://discord.gg/apiato)
