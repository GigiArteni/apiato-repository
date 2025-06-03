# HashId Integration Guide

Learn how to implement secure, user-friendly ID handling with HashIds in your repositories.

## What are HashIds?

HashIds encode numeric IDs into short, unique, non-sequential strings. Instead of exposing database IDs like `123`, you get user-friendly IDs like `gY6N8`.

**Benefits:**
- **Security**: Hide actual database IDs
- **User-friendly**: Short, memorable identifiers  
- **Non-sequential**: Prevents ID guessing attacks
- **Reversible**: Can decode back to original ID
- **URL-safe**: Perfect for REST APIs

## Installation & Setup

### Install HashIds

```bash
composer require hashids/hashids
```

### Apiato Integration

For Apiato projects, HashIds are typically pre-configured. Check your configuration:

```php
// config/apiato.php
'hash-id' => [
    'salt' => env('APP_KEY'),
    'length' => 6,
    'chars' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
],
```

### Repository Configuration

Configure HashId support in `config/repository.php`:

```php
'hashid' => [
    'enabled' => env('HASHID_ENABLED', true),
    'auto_detect' => true,               // Auto-detect HashIds in requests
    'auto_encode' => true,               // Auto-encode IDs in responses
    'min_length' => 4,                   // Minimum HashId length
    'max_length' => 20,                  // Maximum HashId length
    'fields' => ['id', '*_id'],          // Fields to process
    'fallback_to_numeric' => true,       // Fall back to numeric if decode fails
    'cache_decoded_ids' => true,         // Cache decoded IDs
],
```

## Basic HashId Repository Usage

### Enable HashId Support

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\HashIdRepository;

class UserRepository extends BaseRepository
{
    use HashIdRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'role_id' => 'in',  // HashIds work with foreign keys too
    ];

    public function model(): string
    {
        return User::class;
    }
}
```

### Basic Operations with HashIds

```php
// Find by HashId
$user = $this->userRepository->findByHashId('gY6N8');

// Find or fail by HashId
$user = $this->userRepository->findByHashIdOrFail('gY6N8');

// Update by HashId
$user = $this->userRepository->updateByHashId([
    'name' => 'Updated Name'
], 'gY6N8');

// Delete by HashId
$deleted = $this->userRepository->deleteByHashId('gY6N8');

// Encode/Decode manually
$hashId = $this->userRepository->encodeHashId(123);    // Returns: "gY6N8"
$id = $this->userRepository->decodeHashId('gY6N8');    // Returns: 123
```

## API Integration

### Controller with HashIds

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function show(string $hashId)
    {
        // Works seamlessly with HashIds
        $user = $this->userRepository->findByHashIdOrFail($hashId);
        return response()->json($user);
    }

    public function update(Request $request, string $hashId)
    {
        $user = $this->userRepository->updateByHashId(
            $request->validated(),
            $hashId
        );
        return response()->json($user);
    }

    public function destroy(string $hashId)
    {
        $this->userRepository->deleteByHashId($hashId);
        return response()->noContent();
    }
}
```

### Route Model Binding with HashIds

```php
// In your RouteServiceProvider or web.php
Route::bind('user', function ($hashId) {
    return app(UserRepository::class)->findByHashIdOrFail($hashId);
});

// Now you can use route model binding
Route::get('/users/{user}', [UserController::class, 'show']);

// Controller method
public function show(User $user)
{
    return response()->json($user);
}
```

## Advanced HashId Features

### Request Criteria with HashId Support

The `RequestCriteria` automatically handles HashIds:

```php
use Apiato\Repository\Criteria\RequestCriteria;

public function index(Request $request)
{
    // These requests work automatically with HashIds:
    // /api/users?search=id:gY6N8
    // /api/users?filter=role_id:in:abc123,def456
    // /api/posts?search=user_id:gY6N8
    
    return $this->userRepository
        ->pushCriteria(new RequestCriteria($request))
        ->paginate();
}
```

### Supported HashId Query Examples

```bash
# Find by HashId
GET /api/users?search=id:gY6N8

# Multiple HashIds
GET /api/users?search=id:in:gY6N8,kL9M2,pQ4R7

# Foreign key HashIds
GET /api/posts?filter=user_id:gY6N8
GET /api/comments?filter=post_id:in:abc123,def456

# Mixed with other filters
GET /api/users?search=name:like:john;role_id:gY6N8&searchJoin=and
```

### HashId-aware Transformers

```php
<?php

namespace App\Transformers;

use Apiato\Repository\Presenters\BaseTransformer;

class UserTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['posts', 'profile'];

    public function transform($user): array
    {
        // BaseTransformer automatically handles HashId encoding
        return $this->encodeHashIds([
            'id' => $user->id,                    // Encoded to HashId
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,          // Encoded to HashId
            'created_at' => $user->created_at->toISOString(),
        ]);
    }

    public function includePosts($user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }
}
```

### Response with HashIds

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com",
    "role_id": "kL9M2",
    "created_at": "2024-01-15T10:30:00Z",
    "posts": [
      {
        "id": "pQ4R7",
        "title": "My First Post",
        "user_id": "gY6N8"
      }
    ]
  }
}
```

## Custom HashId Implementation

### Custom HashId Configuration

```php
class UserRepository extends BaseRepository
{
    use HashIdRepository;

    protected function initializeHashIds(): void
    {
        // Custom HashIds configuration
        $this->hashIds = new \Hashids\Hashids(
            salt: config('app.key') . '_users',  // User-specific salt
            minHashLength: 8,                    // Longer HashIds
            alphabet: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'  // Uppercase only
        );
    }
}
```

### Model-specific HashIds

```php
class PostRepository extends BaseRepository
{
    use HashIdRepository;

    protected function initializeHashIds(): void
    {
        // Posts get different HashIds than users
        $this->hashIds = new \Hashids\Hashids(
            salt: config('app.key') . '_posts',
            minHashLength: 6,
            alphabet: 'abcdefghijklmnopqrstuvwxyz1234567890'
        );
    }
}
```

### Contextual HashIds

```php
class MultiTenantRepository extends BaseRepository
{
    use HashIdRepository;

    protected function initializeHashIds(): void
    {
        $tenantId = auth()->user()?->tenant_id ?? 'default';
        
        $this->hashIds = new \Hashids\Hashids(
            salt: config('app.key') . "_{$tenantId}",
            minHashLength: 6
        );
    }
}
```

## Error Handling

### Invalid HashIds

```php
class UserController extends Controller
{
    public function show(string $hashId)
    {
        try {
            $user = $this->userRepository->findByHashIdOrFail($hashId);
            return response()->json($user);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'message' => 'The provided ID is invalid or user does not exist'
            ], 404);
        }
    }
}
```

### Graceful Fallback

```php
class HashIdService
{
    public function findUser(string $identifier): ?User
    {
        // Try HashId first
        if ($this->looksLikeHashId($identifier)) {
            $id = $this->userRepository->decodeHashId($identifier);
            if ($id) {
                return $this->userRepository->find($id);
            }
        }
        
        // Fallback to numeric ID (if allowed)
        if (is_numeric($identifier) && config('repository.hashid.fallback_to_numeric')) {
            return $this->userRepository->find((int)$identifier);
        }
        
        return null;
    }
}
```

## Performance Optimization

### Cache Decoded HashIds

```php
class OptimizedHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    protected array $decodedCache = [];

    public function decodeHashId(string $hashId): ?int
    {
        // Check cache first
        if (isset($this->decodedCache[$hashId])) {
            return $this->decodedCache[$hashId];
        }

        $decoded = parent::decodeHashId($hashId);
        
        // Cache the result
        if ($decoded && config('repository.hashid.cache_decoded_ids')) {
            $this->decodedCache[$hashId] = $decoded;
        }

        return $decoded;
    }
}
```

### Batch HashId Operations

```php
class BatchHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    public function findByHashIds(array $hashIds): Collection
    {
        $decodedIds = array_filter(array_map([$this, 'decodeHashId'], $hashIds));
        return $this->findWhereIn('id', $decodedIds);
    }

    public function encodeModelIds(Collection $models): Collection
    {
        return $models->map(function ($model) {
            $model->hashid = $this->encodeHashId($model->id);
            return $model;
        });
    }
}
```

### Redis Cache for HashIds

```php
class CachedHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    public function decodeHashId(string $hashId): ?int
    {
        $cacheKey = "hashid:decode:{$hashId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($hashId) {
            return parent::decodeHashId($hashId);
        });
    }

    public function encodeHashId(int $id): string
    {
        $cacheKey = "hashid:encode:{$id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return parent::encodeHashId($id);
        });
    }
}
```

## Security Considerations

### HashId Salt Management

```php
// .env - Use strong, unique salts
HASHID_SALT_USERS="${APP_KEY}_users_2024"
HASHID_SALT_POSTS="${APP_KEY}_posts_2024"
HASHID_SALT_ORDERS="${APP_KEY}_orders_2024"
```

### Permission-based HashIds

```php
class SecureUserRepository extends BaseRepository
{
    use HashIdRepository;

    public function findByHashIdOrFail(string $hashId): Model
    {
        $user = parent::findByHashIdOrFail($hashId);
        
        // Check permissions
        if (!$this->canAccessUser($user)) {
            throw new AuthorizationException('Access denied');
        }
        
        return $user;
    }

    private function canAccessUser(User $user): bool
    {
        $currentUser = auth()->user();
        
        // Admin can access any user
        if ($currentUser->isAdmin()) {
            return true;
        }
        
        // Users can only access their own data
        return $currentUser->id === $user->id;
    }
}
```

### Rate Limiting HashId Decoding

```php
class RateLimitedHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    public function decodeHashId(string $hashId): ?int
    {
        $key = 'hashid_decode:' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 100)) {
            throw new TooManyRequestsException('Too many decode attempts');
        }
        
        RateLimiter::hit($key);
        
        return parent::decodeHashId($hashId);
    }
}
```

## Testing HashIds

### Unit Tests

```php
use Tests\TestCase;
use App\Repositories\UserRepository;

class HashIdRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_can_encode_and_decode_hash_id(): void
    {
        $id = 123;
        $hashId = $this->repository->encodeHashId($id);
        $decodedId = $this->repository->decodeHashId($hashId);

        $this->assertNotEquals($id, $hashId);
        $this->assertEquals($id, $decodedId);
    }

    public function test_can_find_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);
        
        $found = $this->repository->findByHashId($hashId);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_returns_null_for_invalid_hash_id(): void
    {
        $found = $this->repository->findByHashId('invalid');
        $this->assertNull($found);
    }
}
```

### Feature Tests

```php
class HashIdApiTest extends TestCase
{
    public function test_api_returns_hash_ids(): void
    {
        $user = User::factory()->create();
        
        $response = $this->getJson("/api/users/{$user->id}");
        
        $response->assertStatus(200);
        $this->assertNotEquals($user->id, $response->json('data.id'));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $response->json('data.id'));
    }

    public function test_can_access_user_by_hash_id(): void
    {
        $user = User::factory()->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);
        
        $response = $this->getJson("/api/users/{$hashId}");
        
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}
```

## Best Practices

### 1. Consistent HashId Usage

```php
// Good: Always use HashIds in API responses
class UserController extends Controller
{
    public function show(string $hashId)
    {
        return $this->userRepository->findByHashIdOrFail($hashId);
    }
}

// Bad: Mixing numeric IDs and HashIds
class InconsistentController extends Controller
{
    public function show($id)  // Unclear if HashId or numeric
    {
        return $this->userRepository->find($id);
    }
}
```

### 2. Model-specific Salts

```php
// Good: Different salts for different models
'hashid_salts' => [
    'users' => env('HASHID_SALT_USERS', env('APP_KEY') . '_users'),
    'posts' => env('HASHID_SALT_POSTS', env('APP_KEY') . '_posts'),
    'orders' => env('HASHID_SALT_ORDERS', env('APP_KEY') . '_orders'),
]

// Bad: Same salt for all models (security risk)
'hashid_salt' => env('APP_KEY')
```

### 3. Validation

```php
// Custom validation rule for HashIds
class HashIdRule implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }
        
        return app(UserRepository::class)->decodeHashId($value) !== null;
    }

    public function message()
    {
        return 'The :attribute is not a valid identifier.';
    }
}

// Use in form requests
class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => ['required', new HashIdRule()],
            'name' => 'required|string|max:255',
        ];
    }
}
```

### 4. Documentation

```php
/**
 * @OA\Get(
 *     path="/api/users/{id}",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="User HashId (e.g., 'gY6N8')",
 *         @OA\Schema(type="string", pattern="^[a-zA-Z0-9]+$")
 *     ),
 *     @OA\Response(response=200, description="User details")
 * )
 */
public function show(string $hashId) { }
```

## Troubleshooting

### Common Issues

**1. HashId decode returns null**
```bash
# Check HashId configuration
php artisan tinker
>>> app('hashids')->decode('gY6N8')

# Verify salt consistency
>>> config('apiato.hash-id.salt')
```

**2. Inconsistent HashIds**
```bash
# Different environments might have different salts
# Ensure APP_KEY is consistent across environments
php artisan key:generate --show
```

**3. Performance issues**
```bash
# Enable HashId caching
REPOSITORY_HASHID_CACHE_ENABLED=true

# Monitor decode operations
Log::info('HashId decode', ['hashid' => $hashId, 'decoded' => $decoded]);
```

## Next Steps

- [Fractal Presenters](presenters.md) - Transform data with HashIds
- [Testing Guide](testing.md) - Test HashId functionality
- [Caching Strategy](caching.md) - Cache with HashId awareness
