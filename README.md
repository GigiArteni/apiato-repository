# Apiato Repository

🚀 **Professional Repository Pattern for Laravel with Full Apiato Integration**

Modern, type-safe replacement for l5-repository with enhanced features for Laravel 11/12 and Apiato v13+.

## ✨ Features

- ✅ **Laravel 11/12 Ready** - Built for modern Laravel
- ✅ **Full Apiato Integration** - Native Porto SAP support
- ✅ **Type Safety** - Full PHP 8.1+ type declarations
- ✅ **Advanced Caching** - Tagged cache with auto-invalidation
- ✅ **HashId Support** - Seamless HashId encoding/decoding
- ✅ **Fractal Presenters** - Professional data transformation
- ✅ **Smart Criteria** - Configurable AND/OR search logic
- ✅ **Enhanced Includes** - Lazy loading with count relations
- ✅ **Date/Number Intervals** - Advanced filtering capabilities
- ✅ **Request Validation** - Built-in validation layer
- ✅ **Code Generation** - Artisan commands for rapid development
- ✅ **Comprehensive Tests** - Full test coverage included

## 🚀 Quick Start

### Installation

```bash
composer require apiato/repository
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=repository-config
```

### Generate Repository

```bash
# Basic repository
php artisan make:repository UserRepository --model=User

# With caching and HashId support
php artisan make:repository UserRepository --model=User --cache
```

### Basic Usage

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\HashIdRepository;
use Apiato\Repository\Traits\CacheableRepository;

class UserRepository extends BaseRepository
{
    use HashIdRepository, CacheableRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'created_at' => 'date_between',
        'role_id' => 'in',  // HashId support
    ];

    public function model(): string
    {
        return User::class;
    }
}
```

## 🔧 Advanced Features

### Enhanced API Queries

```bash
# Complex search with HashIds
GET /api/users?search=name:like:john;role_id:in:abc123,def456&searchJoin=and

# Date ranges and shortcuts
GET /api/posts?filter=created_at:date_between:2024-01-01,2024-12-31
GET /api/posts?filter=created_at:this_month

# Smart includes with counts
GET /api/users?include=profile.country,posts_count,notifications_count

# Field comparisons
GET /api/events?compare=start_date:<=:end_date
```

### Fractal Presenters

```php
<?php

use Apiato\Repository\Presenters\BaseTransformer;

class UserTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['profile', 'posts'];

    public function transform($user): array
    {
        return $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'created_at' => $user->created_at->toISOString(),
        ]);
    }

    public function includeProfile($user)
    {
        return $this->item($user->profile, new ProfileTransformer());
    }
}
```

### Smart Caching

```php
// Auto-cache with tags
$users = $this->userRepository
    ->cacheMinutes(120)
    ->pushCriteria(new ActiveUsersCriteria())
    ->paginate();

// Clear specific cache
php artisan repository:clear-cache --tags=users,posts
```

## 📚 Documentation

- [Installation Guide](docs/installation.md)
- [Repository Usage](docs/repositories.md)
- [Criteria System](docs/criteria.md)
- [Caching Strategy](docs/caching.md)
- [HashId Integration](docs/hashids.md)
- [Fractal Presenters](docs/presenters.md)
- [Testing Guide](docs/testing.md)

## 🧪 Testing

```bash
composer test
composer test-coverage
```

## 📄 License

MIT License - see [LICENSE.md](LICENSE.md)

---

Built with ❤️ for the Apiato community
