# Presenters & Transformers - Data Presentation

Complete guide to using Apiato Repository's presentation layer for formatting, transforming, and optimizing data output with automatic HashId encoding and performance enhancements.

## 📚 Table of Contents

- [Understanding Presenters](#-understanding-presenters)
- [Fractal Presenters](#-fractal-presenters)
- [Custom Transformers](#-custom-transformers)
- [HashId Integration](#-hashid-integration)
- [Advanced Transformation](#-advanced-transformation)
- [API Response Formatting](#-api-response-formatting)
- [Performance Optimization](#-performance-optimization)

## 🎭 Understanding Presenters

Presenters provide a clean separation between your data layer and presentation layer, allowing you to format data consistently across your application while maintaining high performance.

### Basic Presenter Concept

```php
// Without Presenter (inconsistent formatting)
class UserController extends Controller
{
    public function show($id)
    {
        $user = User::find($id);
        
        // Manual formatting in controller
        return [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar ? url($user->avatar) : null,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

// With Presenter (consistent, reusable formatting)
class UserController extends Controller
{
    public function show($id)
    {
        $user = $this->repository->find($id);
        // Automatic formatting via presenter
        return $user; // Already formatted!
    }
}
```

### Setting Up Presenters

```php
class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }

    public function presenter()
    {
        return UserPresenter::class; // Automatic formatting
    }
}

// Or set dynamically
$user = $repository
    ->setPresenter(UserPresenter::class)
    ->find($id);
```

## 🔄 Fractal Presenters

Apiato Repository includes enhanced Fractal presenters with automatic HashId encoding and performance optimizations.

### Basic Fractal Presenter

```php
<?php

namespace App\Presenters;

use Apiato\Repository\Presenters\FractalPresenter;
use App\Transformers\UserTransformer;

/**
 * Class UserPresenter
 */
class UserPresenter extends FractalPresenter
{
    /**
     * Transformer class
     */
    public function getTransformer()
    {
        return new UserTransformer();
    }
}
```

### Basic Transformer

```php
<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\User;

/**
 * Class UserTransformer
 */
class UserTransformer extends TransformerAbstract
{
    /**
     * Available includes
     */
    protected array $availableIncludes = [
        'posts',
        'roles', 
        'profile',
        'department',
    ];

    /**
     * Default includes
     */
    protected array $defaultIncludes = [
        'profile',
    ];

    /**
     * Transform user data
     */
    public function transform(User $user)
    {
        return [
            'id' => hashid_encode($user->id), // Auto HashId encoding
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'verified' => (bool) $user->email_verified_at,
            'avatar' => $user->avatar ? url($user->avatar) : null,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ];
    }

    /**
     * Include posts
     */
    public function includePosts(User $user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }

    /**
     * Include roles with HashId
     */
    public function includeRoles(User $user)
    {
        return $this->collection($user->roles, new RoleTransformer());
    }

    /**
     * Include profile
     */
    public function includeProfile(User $user)
    {
        return $this->item($user->profile, new ProfileTransformer());
    }

    /**
     * Include department with HashId
     */
    public function includeDepartment(User $user)
    {
        return $this->item($user->department, new DepartmentTransformer());
    }
}
```

### Using Fractal Includes

```bash
# Basic user data (with default includes)
GET /api/users/gY6N8

# Include specific relationships
GET /api/users/gY6N8?include=posts,roles

# Include nested relationships
GET /api/users/gY6N8?include=posts.comments,roles.permissions

# Multiple includes with nested data
GET /api/users?include=posts.comments.author,profile.avatar,department.manager
```

## 🛠️ Custom Transformers

### Advanced User Transformer

```php
<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\User;
use Carbon\Carbon;

class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'posts', 'roles', 'profile', 'statistics', 'recent_activity'
    ];

    protected array $defaultIncludes = ['profile'];

    public function transform(User $user)
    {
        return [
            // Basic information with HashId
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            
            // Status and verification
            'status' => $user->status,
            'verified' => (bool) $user->email_verified_at,
            'verified_at' => $user->email_verified_at?->toISOString(),
            
            // Formatted dates
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
            'last_login' => $user->last_login_at?->toISOString(),
            
            // Computed fields
            'avatar_url' => $this->getAvatarUrl($user),
            'display_name' => $this->getDisplayName($user),
            'member_since' => $this->getMemberSince($user),
            'is_online' => $this->isUserOnline($user),
            
            // HashId encoded relationships
            'role_ids' => $user->roles->pluck('id')->map(fn($id) => hashid_encode($id)),
            'department_id' => $user->department_id ? hashid_encode($user->department_id) : null,
            
            // Links (HATEOAS)
            'links' => [
                'self' => route('api.users.show', hashid_encode($user->id)),
                'posts' => route('api.users.posts', hashid_encode($user->id)),
                'avatar' => route('api.users.avatar', hashid_encode($user->id)),
            ],
        ];
    }

    protected function getAvatarUrl(User $user): ?string
    {
        if (!$user->avatar) {
            return $this->getGravatarUrl($user->email);
        }
        
        return Storage::disk('public')->url($user->avatar);
    }

    protected function getDisplayName(User $user): string
    {
        return $user->display_name ?: $user->name;
    }

    protected function getMemberSince(User $user): string
    {
        return $user->created_at->format('F Y');
    }

    protected function isUserOnline(User $user): bool
    {
        return $user->last_activity_at && 
               $user->last_activity_at->gt(now()->subMinutes(5));
    }

    protected function getGravatarUrl(string $email): string
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
    }

    // Include methods...
    public function includePosts(User $user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }

    public function includeStatistics(User $user)
    {
        return $this->item([
            'posts_count' => $user->posts_count ?? $user->posts()->count(),
            'followers_count' => $user->followers_count ?? $user->followers()->count(),
            'following_count' => $user->following_count ?? $user->following()->count(),
            'likes_received' => $user->posts()->sum('likes_count'),
            'comments_count' => $user->comments()->count(),
        ], function($stats) {
            return $stats;
        });
    }

    public function includeRecentActivity(User $user)
    {
        $activities = $user->activities()
                          ->latest()
                          ->limit(10)
                          ->get();
                          
        return $this->collection($activities, new ActivityTransformer());
    }
}
```

### Post Transformer with Relationships

```php
<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Post;

class PostTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'author', 'category', 'tags', 'comments', 'likes'
    ];

    public function transform(Post $post)
    {
        return [
            'id' => hashid_encode($post->id),
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'status' => $post->status,
            'published' => (bool) $post->published_at,
            
            // Formatted dates
            'created_at' => $post->created_at->toISOString(),
            'updated_at' => $post->updated_at->toISOString(),
            'published_at' => $post->published_at?->toISOString(),
            
            // Computed fields
            'read_time' => $this->calculateReadTime($post->content),
            'word_count' => str_word_count(strip_tags($post->content)),
            'featured_image' => $this->getFeaturedImage($post),
            
            // HashId encoded relationships
            'author_id' => hashid_encode($post->user_id),
            'category_id' => $post->category_id ? hashid_encode($post->category_id) : null,
            
            // Counts
            'views_count' => $post->views_count ?? 0,
            'likes_count' => $post->likes_count ?? 0,
            'comments_count' => $post->comments_count ?? 0,
            'shares_count' => $post->shares_count ?? 0,
            
            // Links
            'links' => [
                'self' => route('api.posts.show', hashid_encode($post->id)),
                'author' => route('api.users.show', hashid_encode($post->user_id)),
                'comments' => route('api.posts.comments', hashid_encode($post->id)),
                'web' => route('posts.show', $post->slug),
            ],
        ];
    }

    protected function calculateReadTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, ceil($wordCount / 200)); // 200 words per minute
    }

    protected function getFeaturedImage(Post $post): ?array
    {
        if (!$post->featured_image) {
            return null;
        }

        return [
            'url' => Storage::disk('public')->url($post->featured_image),
            'alt' => $post->featured_image_alt,
            'caption' => $post->featured_image_caption,
        ];
    }

    public function includeAuthor(Post $post)
    {
        return $this->item($post->user, new UserTransformer());
    }

    public function includeCategory(Post $post)
    {
        return $this->item($post->category, new CategoryTransformer());
    }

    public function includeTags(Post $post)
    {
        return $this->collection($post->tags, new TagTransformer());
    }

    public function includeComments(Post $post)
    {
        return $this->collection($post->comments, new CommentTransformer());
    }
}
```

## 🔑 HashId Integration

### Automatic HashId Encoding

```php
class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            // Automatic HashId encoding for primary keys
            'id' => hashid_encode($user->id),
            
            // Automatic HashId encoding for foreign keys
            'department_id' => $user->department_id ? hashid_encode($user->department_id) : null,
            'manager_id' => $user->manager_id ? hashid_encode($user->manager_id) : null,
            
            // Array of HashIds
            'role_ids' => $user->roles->pluck('id')->map(fn($id) => hashid_encode($id)),
            'team_ids' => $user->teams->pluck('id')->map(fn($id) => hashid_encode($id)),
            
            // Other fields...
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
```

### HashId Helper Transformer

```php
<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

/**
 * Base transformer with HashId helpers
 */
abstract class BaseTransformer extends TransformerAbstract
{
    /**
     * Encode single ID to HashId
     */
    protected function encodeId($id): ?string
    {
        return $id ? hashid_encode($id) : null;
    }

    /**
     * Encode array of IDs to HashIds
     */
    protected function encodeIds($ids): array
    {
        return collect($ids)->map(fn($id) => hashid_encode($id))->all();
    }

    /**
     * Transform model with automatic ID encoding
     */
    protected function transformWithHashIds($model, array $fields): array
    {
        $data = [];
        
        foreach ($fields as $field => $transformation) {
            $value = $model->$field;
            
            if ($transformation === 'hashid' && $value) {
                $data[$field] = hashid_encode($value);
            } elseif ($transformation === 'hashid_array' && $value) {
                $data[$field] = $this->encodeIds($value);
            } elseif (is_callable($transformation)) {
                $data[$field] = $transformation($value);
            } else {
                $data[$field] = $value;
            }
        }
        
        return $data;
    }
}

// Usage in specific transformers
class UserTransformer extends BaseTransformer
{
    public function transform(User $user)
    {
        return $this->transformWithHashIds($user, [
            'id' => 'hashid',
            'name' => null,
            'email' => null,
            'department_id' => 'hashid',
            'role_ids' => 'hashid_array',
            'created_at' => fn($date) => $date->toISOString(),
        ]);
    }
}
```

## 🔄 Advanced Transformation

### Conditional Field Inclusion

```php
class UserTransformer extends TransformerAbstract
{
    protected $currentUser;

    public function __construct($currentUser = null)
    {
        $this->currentUser = $currentUser;
    }

    public function transform(User $user)
    {
        $data = [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'avatar_url' => $this->getAvatarUrl($user),
            'created_at' => $user->created_at->toISOString(),
        ];

        // Include email only for the user themselves or admins
        if ($this->canViewEmail($user)) {
            $data['email'] = $user->email;
            $data['verified'] = (bool) $user->email_verified_at;
        }

        // Include sensitive data only for admins
        if ($this->canViewSensitiveData()) {
            $data['last_login'] = $user->last_login_at?->toISOString();
            $data['ip_address'] = $user->last_ip;
            $data['login_count'] = $user->login_count;
        }

        // Include private profile data for friends or self
        if ($this->canViewPrivateProfile($user)) {
            $data['phone'] = $user->phone;
            $data['birth_date'] = $user->birth_date?->format('Y-m-d');
            $data['address'] = $user->address;
        }

        return $data;
    }

    protected function canViewEmail(User $user): bool
    {
        return $this->currentUser && (
            $this->currentUser->id === $user->id ||
            $this->currentUser->hasRole('admin')
        );
    }

    protected function canViewSensitiveData(): bool
    {
        return $this->currentUser && $this->currentUser->hasRole('admin');
    }

    protected function canViewPrivateProfile(User $user): bool
    {
        return $this->currentUser && (
            $this->currentUser->id === $user->id ||
            $this->currentUser->isFriend($user) ||
            $this->currentUser->hasRole('admin')
        );
    }
}

// Usage with current user context
$presenter = new UserPresenter($currentUser);
$user = $repository->setPresenter($presenter)->find($id);
```

### Multi-Version API Transformation

```php
<?php

namespace App\Transformers;

class UserTransformer extends TransformerAbstract
{
    protected $apiVersion;

    public function __construct($apiVersion = 'v1')
    {
        $this->apiVersion = $apiVersion;
    }

    public function transform(User $user)
    {
        $baseData = [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'created_at' => $user->created_at->toISOString(),
        ];

        switch ($this->apiVersion) {
            case 'v1':
                return array_merge($baseData, [
                    'email' => $user->email,
                    'status' => $user->status,
                ]);

            case 'v2':
                return array_merge($baseData, [
                    'email' => $user->email,
                    'status' => [
                        'value' => $user->status,
                        'label' => ucfirst($user->status),
                        'color' => $this->getStatusColor($user->status),
                    ],
                    'profile' => [
                        'avatar' => $this->getAvatarUrl($user),
                        'bio' => $user->profile?->bio,
                    ],
                ]);

            case 'v3':
                return array_merge($baseData, [
                    'contact' => [
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'verified' => (bool) $user->email_verified_at,
                    ],
                    'status' => [
                        'current' => $user->status,
                        'previous' => $user->previous_status,
                        'changed_at' => $user->status_changed_at?->toISOString(),
                    ],
                    'metadata' => [
                        'version' => 'v3',
                        'type' => 'user',
                        'etag' => md5($user->updated_at),
                    ],
                ]);

            default:
                return $baseData;
        }
    }

    protected function getStatusColor($status): string
    {
        return match($status) {
            'active' => '#22c55e',
            'pending' => '#f59e0b',
            'suspended' => '#ef4444',
            'banned' => '#991b1b',
            default => '#6b7280',
        };
    }
}

// Usage in controller
class UserController extends Controller
{
    public function show($id)
    {
        $apiVersion = request()->header('API-Version', 'v1');
        
        $transformer = new UserTransformer($apiVersion);
        $presenter = new UserPresenter($transformer);
        
        return $this->repository
            ->setPresenter($presenter)
            ->find($id);
    }
}
```

### Data Transformation Pipeline

```php
<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class PostTransformer extends TransformerAbstract
{
    protected $transformationPipeline = [
        'sanitizeContent',
        'processMarkdown',
        'extractMetadata',
        'generateThumbnails',
    ];

    public function transform(Post $post)
    {
        $data = [
            'id' => hashid_encode($post->id),
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'status' => $post->status,
            'created_at' => $post->created_at->toISOString(),
        ];

        // Apply transformation pipeline
        foreach ($this->transformationPipeline as $method) {
            if (method_exists($this, $method)) {
                $data = $this->$method($data, $post);
            }
        }

        return $data;
    }

    protected function sanitizeContent(array $data, Post $post): array
    {
        $data['content'] = strip_tags($data['content'], '<p><br><strong><em><ul><ol><li>');
        return $data;
    }

    protected function processMarkdown(array $data, Post $post): array
    {
        if ($post->content_type === 'markdown') {
            $data['content_html'] = \Parsedown::instance()->text($post->content);
        }
        return $data;
    }

    protected function extractMetadata(array $data, Post $post): array
    {
        $data['metadata'] = [
            'word_count' => str_word_count(strip_tags($post->content)),
            'read_time' => $this->calculateReadTime($post->content),
            'language' => $this->detectLanguage($post->content),
            'keywords' => $this->extractKeywords($post->content),
        ];
        return $data;
    }

    protected function generateThumbnails(array $data, Post $post): array
    {
        if ($post->featured_image) {
            $data['thumbnails'] = [
                'small' => $this->generateThumbnail($post->featured_image, 150, 150),
                'medium' => $this->generateThumbnail($post->featured_image, 300, 300),
                'large' => $this->generateThumbnail($post->featured_image, 600, 400),
            ];
        }
        return $data;
    }

    // Helper methods...
    protected function calculateReadTime(string $content): int
    {
        return max(1, ceil(str_word_count(strip_tags($content)) / 200));
    }

    protected function detectLanguage(string $content): string
    {
        // Implement language detection logic
        return 'en'; // Default
    }

    protected function extractKeywords(string $content): array
    {
        // Implement keyword extraction logic
        return [];
    }

    protected function generateThumbnail(string $imagePath, int $width, int $height): string
    {
        // Implement thumbnail generation logic
        return '';
    }
}
```

## 🌐 API Response Formatting

### Standardized API Responses

```php
<?php

namespace App\Presenters;

use Apiato\Repository\Presenters\FractalPresenter;
use App\Transformers\BaseApiTransformer;

/**
 * API Response Presenter with standardized format
 */
class ApiResponsePresenter extends FractalPresenter
{
    public function present($data)
    {
        $transformed = parent::present($data);

        // Wrap in standardized API response format
        return [
            'success' => true,
            'data' => $transformed['data'] ?? $transformed,
            'meta' => $this->buildMeta($data, $transformed),
            'links' => $this->buildLinks($data),
            'included' => $transformed['included'] ?? [],
        ];
    }

    protected function buildMeta($originalData, $transformedData): array
    {
        $meta = [
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version', '1.0'),
        ];

        // Add pagination meta if data is paginated
        if (method_exists($originalData, 'total')) {
            $meta['pagination'] = [
                'total' => $originalData->total(),
                'count' => $originalData->count(),
                'per_page' => $originalData->perPage(),
                'current_page' => $originalData->currentPage(),
                'total_pages' => $originalData->lastPage(),
                'has_more' => $originalData->hasMorePages(),
            ];
        }

        return $meta;
    }

    protected function buildLinks($data): array
    {
        $links = [
            'self' => request()->fullUrl(),
        ];

        // Add pagination links if data is paginated
        if (method_exists($data, 'url')) {
            $links['first'] = $data->url(1);
            $links['last'] = $data->url($data->lastPage());
            
            if ($data->previousPageUrl()) {
                $links['prev'] = $data->previousPageUrl();
            }
            
            if ($data->nextPageUrl()) {
                $links['next'] = $data->nextPageUrl();
            }
        }

        return $links;
    }
}

// Usage in repository
class UserRepository extends BaseRepository
{
    public function presenter()
    {
        return ApiResponsePresenter::class;
    }
}

// Example API response
{
    "success": true,
    "data": [
        {
            "id": "gY6N8",
            "name": "John Doe",
            "email": "john@example.com"
        }
    ],
    "meta": {
        "timestamp": "2024-06-03T10:30:00Z",
        "version": "1.0",
        "pagination": {
            "total": 150,
            "count": 15,
            "per_page": 15,
            "current_page": 1,
            "total_pages": 10,
            "has_more": true
        }
    },
    "links": {
        "self": "https://api.example.com/users?page=1",
        "first": "https://api.example.com/users?page=1",
        "last": "https://api.example.com/users?page=10",
        "next": "https://api.example.com/users?page=2"
    },
    "included": []
}
```

### Error Response Transformation

```php
<?php

namespace App\Presenters;

use Apiato\Repository\Presenters\FractalPresenter;

class ErrorResponsePresenter extends FractalPresenter
{
    public function present($error)
    {
        return [
            'success' => false,
            'error' => [
                'type' => $error['type'] ?? 'generic_error',
                'code' => $error['code'] ?? 'E001',
                'message' => $error['message'] ?? 'An error occurred',
                'details' => $error['details'] ?? null,
                'trace_id' => $error['trace_id'] ?? uniqid(),
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => config('app.api_version'),
                'request_id' => request()->id ?? uniqid(),
            ],
            'links' => [
                'documentation' => config('app.docs_url'),
                'support' => config('app.support_url'),
            ],
        ];
    }
}
```

## ⚡ Performance Optimization

### Cached Transformers

```php
<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\Cache;

class CachedUserTransformer extends TransformerAbstract
{
    protected $cacheMinutes = 60;

    public function transform(User $user)
    {
        $cacheKey = "user_transform_{$user->id}_{$user->updated_at->timestamp}";

        return Cache::remember($cacheKey, $this->cacheMinutes, function() use ($user) {
            return [
                'id' => hashid_encode($user->id),
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $this->getAvatarUrl($user),
                'statistics' => $this->calculateStatistics($user),
                'created_at' => $user->created_at->toISOString(),
            ];
        });
    }

    protected function calculateStatistics(User $user): array
    {
        // Expensive calculation that benefits from caching
        return [
            'posts_count' => $user->posts()->count(),
            'followers_count' => $user->followers()->count(),
            'average_post_views' => $user->posts()->avg('views_count'),
            'engagement_rate' => $this->calculateEngagementRate($user),
        ];
    }
}
```

### Optimized Eager Loading

```php
class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['posts', 'profile', 'roles'];

    public function transform(User $user)
    {
        return [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'email' => $user->email,
            
            // Use loaded relationships to avoid N+1 queries
            'posts_count' => $user->posts_count ?? $user->posts->count(),
            'roles_names' => $user->relationLoaded('roles') 
                ? $user->roles->pluck('name') 
                : null,
        ];
    }

    public function includePosts(User $user)
    {
        // Check if relationship is already loaded
        if (!$user->relationLoaded('posts')) {
            $user->load('posts');
        }

        return $this->collection($user->posts, new PostTransformer());
    }
}

// Repository usage with optimized loading
$users = $repository
    ->with(['posts:id,user_id,title', 'roles:id,name']) // Select specific columns
    ->paginate(15);
```

### Batch Transformation

```php
class BatchUserTransformer extends TransformerAbstract
{
    public function transformCollection($users)
    {
        // Pre-load all required data in batches
        $userIds = $users->pluck('id');
        
        // Batch load statistics
        $statistics = $this->batchLoadStatistics($userIds);
        
        // Batch load avatar URLs
        $avatars = $this->batchLoadAvatars($users);

        return $users->map(function($user) use ($statistics, $avatars) {
            return [
                'id' => hashid_encode($user->id),
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $avatars[$user->id] ?? null,
                'statistics' => $statistics[$user->id] ?? [],
                'created_at' => $user->created_at->toISOString(),
            ];
        });
    }

    protected function batchLoadStatistics($userIds): array
    {
        // Single query to get all user statistics
        return DB::table('user_statistics')
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id')
            ->toArray();
    }

    protected function batchLoadAvatars($users): array
    {
        // Process all avatars in batch
        return $users->mapWithKeys(function($user) {
            return [$user->id => $this->getAvatarUrl($user)];
        })->toArray();
    }
}
```

---

**Next:** Learn about **[Caching System](caching.md)** for maximum performance optimization.