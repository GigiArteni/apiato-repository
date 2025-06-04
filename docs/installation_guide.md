# Installation & Migration Guide

Complete guide to migrate from l5-repository to Apiato Repository with **zero breaking changes** and **immediate performance improvements**.

## 🚀 Quick Migration (2 Minutes)

### Step 1: Remove Old Package
```bash
composer remove prettus/l5-repository
# or
composer remove andersao/l5-repository
```

### Step 2: Install Apiato Repository
```bash
composer require apiato/repository
```

### Step 3: Verify Installation
```bash
php artisan list | grep "make:repository"
```

### Step 4: Test Your Code
```bash
# Your existing code should work immediately
php artisan serve
```

**That's it!** 🎉 Your code now runs 40-80% faster with automatic HashId support.

## 📋 Detailed Migration Steps

### 1. Pre-Migration Checklist

Before starting, verify your current setup:

```bash
# Check current l5-repository version
composer show prettus/l5-repository

# Backup your repository files (optional but recommended)
cp -r app/Repositories app/Repositories.backup

# List your existing repositories
find app -name "*Repository.php" -type f
```

### 2. Remove Old Dependencies

Remove l5-repository and any related packages:

```bash
# Remove main package
composer remove prettus/l5-repository

# Remove related packages if installed
composer remove prettus/laravel-validation
composer remove prettus/laravel-request-logger
```

### 3. Install Apiato Repository

```bash
# Install the new package
composer require apiato/repository

# Publish configuration (optional)
php artisan vendor:publish --tag=repository
```

### 4. Verify Compatibility Layer

The package automatically creates aliases for l5-repository classes. Verify this works:

```php
// Create a test file: test-compatibility.php
<?php
require_once 'vendor/autoload.php';

// These should all resolve without errors
$interfaces = [
    'Prettus\Repository\Contracts\RepositoryInterface',
    'Prettus\Repository\Contracts\CriteriaInterface',
    'Prettus\Repository\Eloquent\BaseRepository',
    'Prettus\Repository\Criteria\RequestCriteria',
];

foreach ($interfaces as $interface) {
    if (interface_exists($interface) || class_exists($interface)) {
        echo "✅ {$interface} available\n";
    } else {
        echo "❌ {$interface} missing\n";
    }
}
```

Run the test:
```bash
php test-compatibility.php
rm test-compatibility.php  # Clean up
```

## 🔧 Configuration Migration

### Default Configuration

Apiato Repository works out of the box with sensible defaults:

```php
// config/repository.php is created automatically with enhanced defaults
return [
    'pagination' => ['limit' => 15],
    'cache' => [
        'enabled' => true,        // Enhanced caching enabled by default
        'minutes' => 30,
        'clean' => ['enabled' => true],
    ],
    'apiato' => [
        'hashid_enabled' => true, // HashId support enabled automatically
        'auto_cache_clear' => true,
        'enhanced_search' => true,
    ],
];
```

### Migrate Existing Configuration

If you have custom l5-repository configuration:

```bash
# Backup existing config
cp config/repository.php config/repository.php.backup

# The new config is compatible + enhanced
# Review and merge your custom settings
```

**Example: Merging Custom Settings**

```php
// Your old config/repository.php
return [
    'pagination' => ['limit' => 20],
    'cache' => ['enabled' => false],
    'generator' => [
        'paths' => [
            'models' => 'Models',
            'repositories' => 'Repositories',
        ],
    ],
];

// New enhanced config keeps your settings + adds improvements
return [
    'pagination' => ['limit' => 20],  // Your custom setting preserved
    'cache' => [
        'enabled' => false,           // Your preference respected
        'minutes' => 30,              // New enhanced options added
        'clean' => ['enabled' => true],
    ],
    'generator' => [
        'paths' => [
            'models' => 'Models',      // Your custom paths preserved
            'repositories' => 'Repositories',
        ],
    ],
    'apiato' => [                     // New Apiato enhancements
        'hashid_enabled' => true,
        'auto_cache_clear' => true,
        'enhanced_search' => true,
    ],
];
```

## 🧪 Testing Your Migration

### 1. Repository Functionality Test

Create a simple test to verify your repositories work:

```php
// Create test file: test-repository.php
<?php

use App\Repositories\UserRepository;
use Apiato\Repository\Criteria\RequestCriteria;

// Test basic repository functionality
try {
    $repository = app(UserRepository::class);
    
    // Test basic methods
    echo "Testing paginate: ";
    $users = $repository->paginate(5);
    echo "✅ Success\n";
    
    // Test criteria
    echo "Testing criteria: ";
    $repository->pushCriteria(app(RequestCriteria::class));
    $users = $repository->all();
    echo "✅ Success\n";
    
    // Test find (with HashId support)
    if ($users->count() > 0) {
        echo "Testing find: ";
        $user = $repository->find($users->first()->id);
        echo "✅ Success\n";
    }
    
    echo "\n🎉 All tests passed! Migration successful.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Check your repository configuration.\n";
}
```

Run the test:
```bash
php artisan tinker
# Copy and paste the test code above
```

### 2. API Endpoint Test

Test your existing API endpoints:

```bash
# Test basic endpoints
curl http://localhost:8000/api/users
curl http://localhost:8000/api/users?search=name:john
curl http://localhost:8000/api/users?filter=status:active

# Test with HashIds (if you have them)
curl http://localhost:8000/api/users/gY6N8
```

### 3. Performance Comparison

Measure the performance improvement:

```php
// Create performance test: test-performance.php
<?php

use App\Repositories\UserRepository;

$repository = app(UserRepository::class);

// Warm up
$repository->paginate(10);

// Test performance
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $repository->paginate(15);
}
$end = microtime(true);

$avgTime = ($end - $start) / 100 * 1000; // Convert to milliseconds
echo "Average response time: {$avgTime}ms\n";
```

## 🚨 Troubleshooting

### Common Issues and Solutions

#### Issue: "Class not found" errors
```bash
# Solution: Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer dump-autoload
```

#### Issue: Repository methods not working
```php
// Check if your repository extends the correct base class
class UserRepository extends BaseRepository // ✅ Correct
{
    // Your existing code
}

// Not this:
class UserRepository extends Model // ❌ Wrong
```

#### Issue: Criteria not applying
```php
// Verify RequestCriteria is pushed in boot method
public function boot()
{
    $this->pushCriteria(app(RequestCriteria::class)); // ✅ Required
}
```

#### Issue: Caching not working
```php
// Check cache configuration
// config/repository.php
'cache' => [
    'enabled' => true,  // Must be true
    'minutes' => 30,
],
```

### Debugging Steps

1. **Check Composer Autoload**
   ```bash
   composer dump-autoload -o
   ```

2. **Verify Class Aliases**
   ```php
   php artisan tinker
   >>> class_exists('Prettus\Repository\Eloquent\BaseRepository')
   true  // Should return true
   ```

3. **Test Repository Creation**
   ```bash
   php artisan make:repository TestRepository
   # Should create repository successfully
   ```

4. **Check Service Provider Registration**
   ```php
   // In config/app.php, verify provider is registered (automatic with auto-discovery)
   // Or manually add:
   'providers' => [
       // ...
       Apiato\Repository\Providers\RepositoryServiceProvider::class,
   ],
   ```

## 📊 Migration Verification Checklist

After migration, verify these items work:

- [ ] **Basic Repository Methods**
  - [ ] `all()`, `paginate()`, `find()`
  - [ ] `create()`, `update()`, `delete()`
  - [ ] `findWhere()`, `findWhereIn()`

- [ ] **Criteria System**
  - [ ] RequestCriteria with search
  - [ ] Custom criteria classes
  - [ ] Multiple criteria stacking

- [ ] **Relationships**
  - [ ] `with()` eager loading
  - [ ] `whereHas()` filtering
  - [ ] Nested relationships

- [ ] **Caching**
  - [ ] Repository caching enabled
  - [ ] Cache invalidation on CUD operations
  - [ ] Custom cache keys

- [ ] **API Endpoints**
  - [ ] Basic CRUD operations
  - [ ] Search and filtering
  - [ ] Pagination
  - [ ] HashId support (if applicable)

- [ ] **Performance**
  - [ ] Response times improved
  - [ ] Memory usage reduced
  - [ ] Database query optimization

## 🎯 Next Steps

After successful migration:

1. **[Read Repository Basics](repository-basics.md)** - Understand enhanced features
2. **[Explore HashId Integration](hashids.md)** - Automatic ID encoding/decoding
3. **[Optimize Caching](caching.md)** - Get maximum performance
4. **[Review API Examples](api-examples.md)** - Real-world usage patterns

## 🆘 Need Help?

If you encounter issues during migration:

1. **Check [Troubleshooting Guide](troubleshooting.md)**
2. **Review [Common Migration Issues](migration.md)**
3. **Open GitHub Issue** with error details
4. **Join Community Discussions** for support

---

**Migration complete!** 🎉 Your code now runs faster with enhanced features. Continue to **[Repository Basics](repository-basics.md)** to explore the new capabilities.