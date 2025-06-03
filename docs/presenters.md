# Fractal Presenters Guide

Learn how to implement professional data transformation using Fractal presenters with the Apiato Repository package.

## What are Fractal Presenters?

Fractal is a powerful data transformation layer that provides:
- **Consistent API responses** - Standardized data format
- **Include/Exclude relationships** - Client-controlled data loading
- **Data transformation** - Clean, formatted output
- **Pagination support** - Built-in pagination handling
- **HashId integration** - Automatic ID encoding
- **Resource optimization** - Load only what's needed

## Basic Setup

### Install Fractal

```bash
composer require league/fractal
```

### Create a Transformer

```bash
php artisan make:transformer UserTransformer
```

### Basic Transformer

```php
<?php

namespace App\Transformers;

use App\Models\User;
use Apiato\Repository\Presenters\BaseTransformer;

class UserTransformer extends BaseTransformer
{
    /**
     * Available relationships to include
     */
    protected array $availableIncludes = [
        'profile',
        'posts',
        'comments',
        'role'
    ];

    /**
     * Default relationships to include
     */
    protected array $defaultIncludes = [
        // 'profile'  // Uncomment to always include
    ];

    /**
     * Transform user data
     */
    public function transform(User $user): array
    {
        return $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ]);
    }

    /**
     * Include user profile
     */
    public function includeProfile(User $user)
    {
        if (!$user->profile) {
            return $this->null();
        }

        return $this->item($user->profile, new ProfileTransformer());
    }

    /**
     * Include user posts
     */
    public function includePosts(User $user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }

    /**
     * Include user role
     */
    public function includeRole(User $user)
    {
        if (!$user->role) {
            return $this->null();
        }

        return $this->item($user->role, new RoleTransformer());
    }
}
```

### Repository with Presenter

```php
<?php

namespace App\Repositories;

use App\Models\User;
use App\Presenters\UserPresenter;
use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\HashIdRepository;

class UserRepository extends BaseRepository
{
    use HashIdRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => 'in',
    ];

    public function model(): string
    {
        return User::class;
    }

    public function presenter(): string
    {
        return UserPresenter::class;
    }
}
```

### Create Presenter Class

```php
<?php

namespace App\Presenters;

use App\Transformers\UserTransformer;
use Apiato\Repository\Presenters\FractalPresenter;

class UserPresenter extends FractalPresenter
{
    public function __construct()
    {
        parent::__construct(app(\League\Fractal\Manager::class));
        $this->setTransformer(new UserTransformer());
    }
}
```

## API Usage Examples

### Basic API Responses

```php
// Controller
class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function index()
    {
        // Returns transformed data automatically
        return $this->userRepository->paginate(15);
    }

    public function show(string $hashId)
    {
        return $this->userRepository->findByHashIdOrFail($hashId);
    }
}
```

### API Response Examples

**Basic response:**
```bash
GET /api/users/gY6N8
```

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "status": "active",
    "email_verified_at": "2024-01-15T10:30:00Z",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

**With includes:**
```bash
GET /api/users/gY6N8?include=profile,posts
```

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com",
    "profile": {
      "data": {
        "id": "kL9M2",
        "bio": "Software developer",
        "avatar": "https://example.com/avatar.jpg",
        "country": "USA"
      }
    },
    "posts": {
      "data": [
        {
          "id": "pQ4R7",
          "title": "My First Post",
          "slug": "my-first-post",
          "excerpt": "This is my first post...",
          "created_at": "2024-01-16T14:20:00Z"
        }
      ]
    }
  }
}
```

**Paginated response:**
```bash
GET /api/users?include=profile
```

```json
{
  "data": [
    {
      "id": "gY6N8",
      "name": "John Doe",
      "profile": {
        "data": {
          "id": "kL9M2",
          "bio": "Software developer"
        }
      }
    }
  ],
  "meta": {
    "pagination": {
      "total": 150,
      "per_page": 15,
      "current_page": 1,
      "last_page": 10,
      "from": 1,
      "to": 15,
      "path": "http://api.example.com/users",
      "next_page_url": "http://api.example.com/users?page=2",
      "prev_page_url": null
    }
  }
}
```

## Advanced Transformer Features

### Conditional Fields

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString(),
        ]);

        // Add admin-only fields
        if (auth()->user()?->isAdmin()) {
            $data['internal_notes'] = $user->internal_notes;
            $data['last_login_ip'] = $user->last_login_ip;
        }

        // Add owner-only fields
        if (auth()->id() === $user->id) {
            $data['email_verified_at'] = $user->email_verified_at?->toISOString();
            $data['two_factor_enabled'] = $user->two_factor_enabled;
        }

        return $data;
    }
}
```

### Dynamic Transformers

```php
class PostTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['author', 'comments', 'tags', 'category'];

    public function transform(Post $post): array
    {
        $data = $this->encodeHashIds([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status,
            'published_at' => $post->published_at?->toISOString(),
            'created_at' => $post->created_at->toISOString(),
        ]);

        // Add content based on status
        if ($post->status === 'published' || auth()->user()?->can('view', $post)) {
            $data['content'] = $post->content;
            $data['excerpt'] = $post->excerpt;
        } else {
            $data['excerpt'] = 'This post is not available.';
        }

        return $data;
    }

    public function includeAuthor(Post $post)
    {
        return $this->item($post->user, new UserTransformer());
    }

    public function includeComments(Post $post)
    {
        // Only include published comments
        $publishedComments = $post->comments()->where('status', 'approved')->get();
        return $this->collection($publishedComments, new CommentTransformer());
    }

    public function includeTags(Post $post)
    {
        return $this->collection($post->tags, new TagTransformer());
    }
}
```

### Nested Includes

```php
class OrderTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['items', 'customer', 'shipping_address'];

    public function transform(Order $order): array
    {
        return $this->encodeHashIds([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->created_at->toISOString(),
        ]);
    }

    public function includeItems(Order $order)
    {
        return $this->collection($order->items, new OrderItemTransformer());
    }

    public function includeCustomer(Order $order)
    {
        return $this->item($order->customer, new CustomerTransformer());
    }
}

class OrderItemTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['product', 'product.category'];

    public function transform(OrderItem $item): array
    {
        return $this->encodeHashIds([
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'total_price' => $item->total_price,
        ]);
    }

    public function includeProduct(OrderItem $item)
    {
        return $this->item($item->product, new ProductTransformer());
    }
}
```

**Usage with nested includes:**
```bash
GET /api/orders?include=items.product.category,customer
```

### Count Relationships

```php
class UserTransformer extends BaseTransformer
{
    protected array $availableIncludes = [
        'posts',
        'posts_count',
        'comments_count',
        'followers_count'
    ];

    public function includePostsCount(User $user)
    {
        return $this->primitive($user->posts()->count());
    }

    public function includeCommentsCount(User $user)
    {
        return $this->primitive($user->comments()->count());
    }

    public function includeFollowersCount(User $user)
    {
        return $this->primitive($user->followers()->count());
    }
}
```

**Usage:**
```bash
GET /api/users?include=posts_count,comments_count,followers_count
```

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "posts_count": 25,
    "comments_count": 147,
    "followers_count": 532
  }
}
```

## Sparse Fieldsets

### Enable Sparse Fieldsets

Configure in `config/repository.php`:

```php
'fractal' => [
    'params' => [
        'include' => 'include',
        'exclude' => 'exclude',
        'fields' => 'fields',    // Enable sparse fieldsets
    ],
],
```

### Usage Examples

```bash
# Only specific fields
GET /api/users?fields=id,name,email

# Fields with includes
GET /api/users?fields=id,name,posts&include=posts
GET /api/posts?fields[posts]=id,title,slug&fields[users]=id,name&include=author
```

### Transformer Support

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        // Full data set
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'created_at' => $user->created_at->toISOString(),
        ]);

        // Fractal will automatically filter fields based on request
        return $data;
    }
}
```

## Performance Optimization

### Eager Loading with Includes

```php
class UserRepository extends BaseRepository
{
    public function findWithIncludes(string $hashId, array $includes = []): ?Model
    {
        $id = $this->decodeHashId($hashId);
        
        if (!$id) {
            return null;
        }

        $query = $this->query();

        // Optimize based on requested includes
        if (in_array('profile', $includes)) {
            $query->with('profile');
        }

        if (in_array('posts', $includes)) {
            $query->with(['posts' => function ($q) {
                $q->where('status', 'published')
                  ->latest()
                  ->limit(10);
            }]);
        }

        if (in_array('posts_count', $includes)) {
            $query->withCount('posts');
        }

        return $query->find($id);
    }
}
```

### Lazy Loading Control

```php
class PostTransformer extends BaseTransformer
{
    public function includeComments(Post $post)
    {
        // Only load comments if not already loaded
        if (!$post->relationLoaded('comments')) {
            $post->load(['comments' => function ($query) {
                $query->where('status', 'approved')
                      ->with('author:id,name')
                      ->latest()
                      ->limit(5);
            }]);
        }

        return $this->collection($post->comments, new CommentTransformer());
    }
}
```

### Caching Transformed Data

```php
class CachedUserPresenter extends FractalPresenter
{
    public function present(mixed $data): mixed
    {
        if ($data instanceof Model) {
            $cacheKey = "transformed_user_{$data->id}_" . md5(serialize(request()->query()));
            
            return Cache::remember($cacheKey, 300, function () use ($data) {
                return parent::present($data);
            });
        }

        return parent::present($data);
    }
}
```

## Custom Serializers

### Create Custom Serializer

```php
<?php

namespace App\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class ApiSerializer extends ArraySerializer
{
    public function collection(?string $resourceKey, array $data): array
    {
        return [
            'data' => $data,
            'status' => 'success',
            'timestamp' => now()->toISOString(),
        ];
    }

    public function item(?string $resourceKey, array $data): array
    {
        return [
            'data' => $data,
            'status' => 'success',
            'timestamp' => now()->toISOString(),
        ];
    }

    public function null(): array
    {
        return [
            'data' => null,
            'status' => 'success',
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

### Configure Custom Serializer

```php
// config/repository.php
'fractal' => [
    'serializer' => App\Serializers\ApiSerializer::class,
],
```

### API Response with Custom Serializer

```json
{
  "data": {
    "id": "gY6N8",
    "name": "John Doe",
    "email": "john@example.com"
  },
  "status": "success",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

## Error Handling

### Transformer Exceptions

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        try {
            return $this->encodeHashIds([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $this->getAvatarUrl($user),
                'created_at' => $user->created_at->toISOString(),
            ]);
        } catch (\Exception $e) {
            // Log error and return safe fallback
            Log::error('User transformation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->encodeHashIds([
                'id' => $user->id,
                'name' => $user->name ?: 'Unknown User',
                'email' => $user->email,
                'avatar' => null,
                'created_at' => $user->created_at->toISOString(),
            ]);
        }
    }

    private function getAvatarUrl(User $user): ?string
    {
        if (!$user->avatar) {
            return null;
        }

        return Storage::disk('public')->url($user->avatar);
    }
}
```

### Missing Relationship Handling

```php
class PostTransformer extends BaseTransformer
{
    public function includeAuthor(Post $post)
    {
        // Handle soft-deleted users
        if (!$post->author || $post->author->trashed()) {
            return $this->item((object)[
                'id' => null,
                'name' => 'Deleted User',
                'email' => null,
            ], function ($data) {
                return [
                    'id' => null,
                    'name' => $data->name,
                    'email' => null,
                ];
            });
        }

        return $this->item($post->author, new UserTransformer());
    }
}
```

## Testing Transformers

### Unit Tests

```php
use Tests\TestCase;
use App\Models\User;
use App\Transformers\UserTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UserTransformerTest extends TestCase
{
    public function test_transforms_user_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $transformer = new UserTransformer();
        $result = $transformer->transform($user);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_encodes_hash_ids(): void
    {
        $user = User::factory()->create();
        $transformer = new UserTransformer();
        $result = $transformer->transform($user);

        $this->assertNotEquals($user->id, $result['id']);
        $this->assertIsString($result['id']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result['id']);
    }

    public function test_includes_profile_relationship(): void
    {
        $user = User::factory()->hasProfile()->create();
        
        $manager = new Manager();
        $manager->parseIncludes(['profile']);
        
        $resource = new Item($user, new UserTransformer());
        $result = $manager->createData($resource)->toArray();

        $this->assertArrayHasKey('profile', $result['data']);
        $this->assertArrayHasKey('data', $result['data']['profile']);
    }
}
```

### Integration Tests

```php
class UserApiTransformationTest extends TestCase
{
    public function test_api_returns_transformed_user_data(): void
    {
        $user = User::factory()->create();
        
        $response = $this->getJson("/api/users/{$user->hashid}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]
        ]);

        // Ensure ID is HashId, not numeric
        $this->assertNotEquals($user->id, $response->json('data.id'));
    }

    public function test_api_includes_work_correctly(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(3)->create();
        
        $response = $this->getJson("/api/users/{$user->hashid}?include=profile,posts");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'profile' => ['data'],
                'posts' => ['data' => [['id', 'title']]],
            ]
        ]);
    }
}
```

## Best Practices

### 1. Consistent Transformation

```php
// Good: Consistent field naming and structure
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        return $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString(),  // Consistent date format
            'updated_at' => $user->updated_at->toISOString(),
        ]);
    }
}

// Bad: Inconsistent field naming
class InconsistentTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        return [
            'user_id' => $user->id,      // Inconsistent with 'id'
            'userName' => $user->name,   // CamelCase vs snake_case
            'created' => $user->created_at->format('Y-m-d'),  // Different date format
        ];
    }
}
```

### 2. Resource Optimization

```php
// Good: Optimized includes
class PostTransformer extends BaseTransformer
{
    public function includeComments(Post $post)
    {
        // Load only what's needed
        $comments = $post->comments()
            ->with('author:id,name,avatar')
            ->where('status', 'approved')
            ->latest()
            ->limit(5)
            ->get();
            
        return $this->collection($comments, new CommentTransformer());
    }
}
```

### 3. Security in Transformers

```php
class UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        // Only show sensitive data to authorized users
        if (auth()->user()?->can('viewSensitive', $user)) {
            $data['phone'] = $user->phone;
            $data['address'] = $user->address;
        }

        return $data;
    }
}
```

### 4. Version-aware Transformers

```php
class V1UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        // Legacy API format
        return $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}

class V2UserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        // New API format with additional fields
        return $this->encodeHashIds([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'username' => $user->username,
            'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ]);
    }
}
```

## Advanced Use Cases

### Multi-format Responses

```php
class FlexibleUserTransformer extends BaseTransformer
{
    protected string $format;

    public function __construct(string $format = 'full')
    {
        parent::__construct();
        $this->format = $format;
    }

    public function transform(User $user): array
    {
        switch ($this->format) {
            case 'minimal':
                return $this->encodeHashIds([
                    'id' => $user->id,
                    'name' => $user->name,
                ]);

            case 'summary':
                return $this->encodeHashIds([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ]);

            default: // full
                return $this->encodeHashIds([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'status' => $user->status,
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                ]);
        }
    }
}

// Usage in presenter
class FlexibleUserPresenter extends FractalPresenter
{
    public function __construct(string $format = 'full')
    {
        parent::__construct(app(\League\Fractal\Manager::class));
        $this->setTransformer(new FlexibleUserTransformer($format));
    }
}
```

### Metadata Enhancement

```php
class EnhancedUserTransformer extends BaseTransformer
{
    public function transform(User $user): array
    {
        $data = $this->encodeHashIds([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString(),
        ]);

        // Add computed fields
        $data['display_name'] = $user->display_name ?? $user->name;
        $data['initials'] = $this->getInitials($user->name);
        $data['member_since'] = $user->created_at->diffForHumans();
        $data['is_online'] = $user->last_seen_at?->gt(now()->subMinutes(5)) ?? false;

        return $data;
    }

    private function getInitials(string $name): string
    {
        return collect(explode(' ', $name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }
}
```

## Next Steps

- [Testing Guide](testing.md) - Test your presenters and transformers
- [Caching Strategy](caching.md) - Cache transformed data efficiently
- [HashId Integration](hashids.md) - Secure ID handling in transformers
