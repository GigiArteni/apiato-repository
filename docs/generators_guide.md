# Generators - Code Generation & Scaffolding

Complete guide to Apiato Repository's powerful code generation system for creating repositories, criteria, presenters, and complete application stacks with intelligent scaffolding.

## 📚 Table of Contents

- [Understanding Generators](#-understanding-generators)
- [Available Commands](#-available-commands)
- [Repository Generation](#-repository-generation)
- [Complete Entity Generation](#-complete-entity-generation)
- [Custom Generator Templates](#-custom-generator-templates)
- [Advanced Scaffolding](#-advanced-scaffolding)
- [Configuration & Customization](#-configuration--customization)
- [Best Practices](#-best-practices)

## 🛠️ Understanding Generators

Apiato Repository generators provide intelligent code scaffolding that creates complete, production-ready classes with proper structure, documentation, and best practices built-in.

### What Generators Create

```php
/**
 * Complete application stack generation:
 * 
 * ✅ Models with relationships
 * ✅ Repositories with caching
 * ✅ Criteria for filtering
 * ✅ Presenters & Transformers
 * ✅ Validators with business rules
 * ✅ Controllers with full CRUD
 * ✅ API routes
 * ✅ Tests (Unit & Feature)
 * ✅ Documentation
 * ✅ Migrations
 */
```

### Benefits

```php
/**
 * Generator advantages:
 * 
 * ⚡ 10x faster development
 * 🏗️ Consistent code structure
 * 📚 Built-in documentation
 * 🧪 Automatic test generation
 * 🔧 Best practices enforced
 * 🎯 Zero configuration needed
 * 🔄 Easy customization
 * 📈 Scalable architecture
 */
```

## 📋 Available Commands

### Core Generator Commands

```bash
# Repository generation
php artisan make:repository UserRepository
php artisan make:repository PostRepository --model=Post

# Entity generation (complete stack)
php artisan make:entity User
php artisan make:entity Post --fillable=title,content,status

# Criteria generation
php artisan make:criteria ActiveUsersCriteria
php artisan make:criteria PostsByCategoryCriteria

# Presenter generation
php artisan make:presenter UserPresenter
php artisan make:presenter PostPresenter --transformer=PostTransformer

# Transformer generation
php artisan make:transformer UserTransformer
php artisan make:transformer PostTransformer --fields=title,content,status

# Validator generation
php artisan make:validator UserValidator
php artisan make:validator PostValidator --rules=create,update

# Complete API generation
php artisan make:api-resource User
php artisan make:api-resource Post --full-crud
```

### Advanced Commands

```bash
# Generate with relationships
php artisan make:entity Post --relations=user:belongsTo,comments:hasMany,tags:belongsToMany

# Generate with custom namespace
php artisan make:repository Admin/UserRepository --namespace=App\\Admin

# Generate with specific template
php artisan make:repository UserRepository --template=enhanced

# Batch generation
php artisan make:batch-entities User,Post,Comment --with-api

# Generate from existing model
php artisan make:repository-from-model User --with-criteria

# Generate test files
php artisan make:repository-tests UserRepository --feature --unit
```

## 🏗️ Repository Generation

### Basic Repository Generation

```bash
# Generate basic repository
php artisan make:repository UserRepository
```

**Generated File: `app/Repositories/UserRepository.php`**

```php
<?php

namespace App\Repositories;

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use App\Models\User;
use App\Presenters\UserPresenter;

/**
 * Class UserRepository
 * @package App\Repositories
 */
class UserRepository extends BaseRepository
{
    /**
     * Specify fields that are searchable
     */
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => 'in',
        'created_at' => 'between',
        // Add more searchable fields as needed
    ];

    /**
     * Specify Model class name
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Specify Presenter class name (optional)
     */
    public function presenter()
    {
        return UserPresenter::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * Get active users
     */
    public function getActiveUsers()
    {
        return $this->findWhere(['status' => 'active']);
    }

    /**
     * Get users by role (supports HashId)
     */
    public function getUsersByRole($roleId)
    {
        return $this->findWhere(['role_id' => $roleId]);
    }

    /**
     * Search users by name or email
     */
    public function searchUsers($query)
    {
        return $this->scopeQuery(function($q) use ($query) {
            return $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
        })->paginate(15);
    }
}
```

### Enhanced Repository Generation

```bash
# Generate enhanced repository with additional features
php artisan make:repository UserRepository --enhanced --with-cache --with-events
```

**Generated Enhanced Repository:**

```php
<?php

namespace App\Repositories;

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use App\Models\User;
use App\Presenters\UserPresenter;
use App\Validators\UserValidator;
use App\Events\UserCreated;
use App\Events\UserUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class UserRepository
 * Enhanced repository with caching, events, and validation
 * 
 * @package App\Repositories
 */
class UserRepository extends BaseRepository
{
    /**
     * Cache configuration
     */
    protected $cacheMinutes = 60;
    protected $cacheEnabled = true;

    /**
     * Specify fields that are searchable
     */
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'username' => 'like',
        'status' => 'in',
        'role_id' => '=',
        'department_id' => '=',
        'created_at' => 'between',
        'updated_at' => 'between',
        'last_login_at' => 'between',
    ];

    /**
     * Specify Model class name
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Specify Presenter class name
     */
    public function presenter()
    {
        return UserPresenter::class;
    }

    /**
     * Specify Validator class name
     */
    public function validator()
    {
        return UserValidator::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * Create user with enhanced features
     */
    public function createUser(array $data)
    {
        $user = $this->create($data);
        
        // Fire custom event
        event(new UserCreated($user));
        
        // Log creation
        Log::info('User created', ['user_id' => $user->id, 'email' => $user->email]);
        
        return $user;
    }

    /**
     * Update user with enhanced features
     */
    public function updateUser($id, array $data)
    {
        $user = $this->update($data, $id);
        
        // Fire custom event
        event(new UserUpdated($user));
        
        // Clear user-specific caches
        $this->clearUserCaches($user);
        
        return $user;
    }

    /**
     * Get active users with caching
     */
    public function getActiveUsers($useCache = true)
    {
        if (!$useCache) {
            return $this->skipCache()->findWhere(['status' => 'active']);
        }

        return Cache::remember('active_users', $this->cacheMinutes, function() {
            return $this->findWhere(['status' => 'active']);
        });
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics()
    {
        return Cache::remember('user_statistics', 1440, function() {
            return [
                'total' => $this->count(),
                'active' => $this->countWhere(['status' => 'active']),
                'pending' => $this->countWhere(['status' => 'pending']),
                'suspended' => $this->countWhere(['status' => 'suspended']),
                'new_today' => $this->countWhere([
                    ['created_at', '>=', now()->startOfDay()]
                ]),
            ];
        });
    }

    /**
     * Advanced user search
     */
    public function advancedSearch(array $criteria = [])
    {
        return $this->scopeQuery(function($query) use ($criteria) {
            // Basic search
            if (!empty($criteria['search'])) {
                $search = $criteria['search'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }

            // Status filter
            if (!empty($criteria['status'])) {
                $query->whereIn('status', (array) $criteria['status']);
            }

            // Role filter (HashId support)
            if (!empty($criteria['role_id'])) {
                $query->where('role_id', $criteria['role_id']);
            }

            // Date range filter
            if (!empty($criteria['created_from'])) {
                $query->where('created_at', '>=', $criteria['created_from']);
            }
            if (!empty($criteria['created_to'])) {
                $query->where('created_at', '<=', $criteria['created_to']);
            }

            return $query;
        })->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Clear user-specific caches
     */
    protected function clearUserCaches($user)
    {
        $tags = [
            "user:{$user->id}",
            'users',
            "status:{$user->status}",
            "role:{$user->role_id}",
        ];

        Cache::tags($tags)->flush();
    }
}
```

### Repository with Custom Methods

```bash
# Generate repository with custom business methods
php artisan make:repository UserRepository --methods=findByEmail,getRecentlyActive,updateLastLogin
```

**Generated with Custom Methods:**

```php
class UserRepository extends BaseRepository
{
    // ... base configuration ...

    /**
     * Find user by email address
     */
    public function findByEmail($email)
    {
        return $this->findWhere(['email' => $email])->first();
    }

    /**
     * Get recently active users
     */
    public function getRecentlyActive($days = 30)
    {
        return $this->scopeQuery(function($query) use ($days) {
            return $query->where('last_activity_at', '>=', now()->subDays($days))
                        ->orderBy('last_activity_at', 'desc');
        })->paginate(15);
    }

    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin($userId, $ipAddress = null)
    {
        $data = [
            'last_login_at' => now(),
            'login_count' => \DB::raw('login_count + 1'),
        ];

        if ($ipAddress) {
            $data['last_ip'] = $ipAddress;
        }

        return $this->update($data, $userId);
    }
}
```

## 🎯 Complete Entity Generation

### Basic Entity Generation

```bash
# Generate complete entity stack
php artisan make:entity User
```

**This creates:**
- Model (`app/Models/User.php`)
- Repository (`app/Repositories/UserRepository.php`)
- Presenter (`app/Presenters/UserPresenter.php`)
- Transformer (`app/Transformers/UserTransformer.php`)
- Validator (`app/Validators/UserValidator.php`)
- Migration (`database/migrations/xxxx_create_users_table.php`)

### Enhanced Entity Generation

```bash
# Generate entity with all features
php artisan make:entity Post \
    --fillable=title,content,status,published_at \
    --relations=user:belongsTo,comments:hasMany,tags:belongsToMany \
    --searchable=title:like,content:like,status:in \
    --with-api \
    --with-tests
```

**Generated Model: `app/Models/Post.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Post
 * 
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $status
 * @property \Carbon\Carbon $published_at
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection $comments
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'content', 
        'status',
        'published_at',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the post
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the post
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the tags for the post
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    /**
     * Scope for published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope for draft posts
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'id'; // Can be changed to 'slug' for SEO-friendly URLs
    }
}
```

**Generated Repository: `app/Repositories/PostRepository.php`**

```php
<?php

namespace App\Repositories;

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use App\Models\Post;
use App\Presenters\PostPresenter;
use App\Validators\PostValidator;

/**
 * Class PostRepository
 * @package App\Repositories
 */
class PostRepository extends BaseRepository
{
    /**
     * Specify fields that are searchable
     */
    protected $fieldSearchable = [
        'title' => 'like',
        'content' => 'like',
        'status' => 'in',
        'user_id' => '=',
        'published_at' => 'between',
        'created_at' => 'between',
    ];

    /**
     * Specify Model class name
     */
    public function model()
    {
        return Post::class;
    }

    /**
     * Specify Presenter class name
     */
    public function presenter()
    {
        return PostPresenter::class;
    }

    /**
     * Specify Validator class name
     */
    public function validator()
    {
        return PostValidator::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * Get published posts
     */
    public function getPublishedPosts($limit = 15)
    {
        return $this->scopeQuery(function($query) {
            return $query->published()->with(['user', 'tags']);
        })->paginate($limit);
    }

    /**
     * Get posts by user (supports HashId)
     */
    public function getPostsByUser($userId, $status = null)
    {
        $where = ['user_id' => $userId];
        
        if ($status) {
            $where['status'] = $status;
        }
        
        return $this->with(['tags'])->findWhere($where);
    }

    /**
     * Get posts by tag
     */
    public function getPostsByTag($tagId)
    {
        return $this->scopeQuery(function($query) use ($tagId) {
            return $query->whereHas('tags', function($q) use ($tagId) {
                $q->where('id', $tagId);
            });
        })->paginate(15);
    }

    /**
     * Publish post
     */
    public function publishPost($id)
    {
        return $this->update([
            'status' => 'published',
            'published_at' => now(),
        ], $id);
    }

    /**
     * Get post statistics
     */
    public function getPostStatistics()
    {
        return [
            'total' => $this->count(),
            'published' => $this->countWhere(['status' => 'published']),
            'draft' => $this->countWhere(['status' => 'draft']),
            'today' => $this->countWhere([
                ['created_at', '>=', now()->startOfDay()]
            ]),
        ];
    }
}
```

**Generated Transformer: `app/Transformers/PostTransformer.php`**

```php
<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Post;
use App\Transformers\UserTransformer;
use App\Transformers\TagTransformer;

/**
 * Class PostTransformer
 * @package App\Transformers
 */
class PostTransformer extends TransformerAbstract
{
    /**
     * Available includes
     */
    protected array $availableIncludes = [
        'user',
        'tags', 
        'comments',
    ];

    /**
     * Default includes
     */
    protected array $defaultIncludes = [
        'user',
    ];

    /**
     * Transform the Post entity
     */
    public function transform(Post $post)
    {
        return [
            'id' => hashid_encode($post->id),
            'title' => $post->title,
            'content' => $post->content,
            'excerpt' => $this->generateExcerpt($post->content),
            'status' => $post->status,
            'published' => $post->status === 'published',
            'slug' => \Str::slug($post->title),
            
            // Computed fields
            'word_count' => str_word_count(strip_tags($post->content)),
            'read_time' => $this->calculateReadTime($post->content),
            
            // HashId encoded relationships
            'user_id' => hashid_encode($post->user_id),
            
            // Dates
            'published_at' => $post->published_at?->toISOString(),
            'created_at' => $post->created_at->toISOString(),
            'updated_at' => $post->updated_at->toISOString(),
            
            // Links (HATEOAS)
            'links' => [
                'self' => route('api.posts.show', hashid_encode($post->id)),
                'user' => route('api.users.show', hashid_encode($post->user_id)),
                'comments' => route('api.posts.comments', hashid_encode($post->id)),
            ],
        ];
    }

    /**
     * Include User
     */
    public function includeUser(Post $post)
    {
        return $this->item($post->user, new UserTransformer());
    }

    /**
     * Include Tags
     */
    public function includeTags(Post $post)
    {
        return $this->collection($post->tags, new TagTransformer());
    }

    /**
     * Include Comments
     */
    public function includeComments(Post $post)
    {
        return $this->collection($post->comments, new CommentTransformer());
    }

    /**
     * Generate excerpt from content
     */
    protected function generateExcerpt($content, $length = 150): string
    {
        $text = strip_tags($content);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }

    /**
     * Calculate reading time
     */
    protected function calculateReadTime($content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, ceil($wordCount / 200)); // 200 words per minute
    }
}
```

### API Generation

```bash
# Generate complete API stack
php artisan make:api-resource Post --full-crud --with-tests
```

**Generated Controller: `app/Http/Controllers/Api/PostController.php`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Repositories\PostRepository;
use Illuminate\Http\Request;

/**
 * Class PostController
 * @package App\Http\Controllers\Api
 */
class PostController extends Controller
{
    protected PostRepository $repository;

    public function __construct(PostRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of posts
     * 
     * @api {get} /posts Get Posts
     * @apiName GetPosts
     * @apiGroup Posts
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $posts = $this->repository->paginate($request->get('per_page', 15));

        return response()->json($posts);
    }

    /**
     * Store a newly created post
     * 
     * @api {post} /posts Create Post
     * @apiName CreatePost
     * @apiGroup Posts
     * 
     * @param CreatePostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreatePostRequest $request)
    {
        try {
            $post = $this->repository->create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $post,
                'message' => 'Post created successfully.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified post
     * 
     * @api {get} /posts/:id Get Post
     * @apiName GetPost  
     * @apiGroup Posts
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $post = $this->repository->find($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $post,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified post
     * 
     * @api {put} /posts/:id Update Post
     * @apiName UpdatePost
     * @apiGroup Posts
     * 
     * @param UpdatePostRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePostRequest $request, $id)
    {
        try {
            $post = $this->repository->update($request->validated(), $id);

            return response()->json([
                'success' => true,
                'data' => $post,
                'message' => 'Post updated successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified post
     * 
     * @api {delete} /posts/:id Delete Post
     * @apiName DeletePost
     * @apiGroup Posts
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $deleted = $this->repository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get published posts
     * 
     * @api {get} /posts/published Get Published Posts
     * @apiName GetPublishedPosts
     * @apiGroup Posts
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function published(Request $request)
    {
        $posts = $this->repository->getPublishedPosts(
            $request->get('per_page', 15)
        );

        return response()->json($posts);
    }

    /**
     * Publish a post
     * 
     * @api {patch} /posts/:id/publish Publish Post
     * @apiName PublishPost
     * @apiGroup Posts
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function publish($id)
    {
        try {
            $post = $this->repository->publishPost($id);

            return response()->json([
                'success' => true,
                'data' => $post,
                'message' => 'Post published successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

## 🎨 Custom Generator Templates

### Creating Custom Templates

```bash
# Publish generator templates for customization
php artisan vendor:publish --tag=repository-templates
```

**Custom Repository Template: `resources/stubs/repository.stub`**

```php
<?php

namespace {{namespace}};

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use {{modelNamespace}};
{{presenterNamespace}}
{{validatorNamespace}}

/**
 * Class {{class}}
 * 
 * {{description}}
 * 
 * @package {{package}}
 * @author {{author}}
 * @version {{version}}
 */
class {{class}} extends BaseRepository
{
    /**
     * Cache settings
     */
    protected $cacheMinutes = {{cacheMinutes}};
    protected $cacheEnabled = {{cacheEnabled}};

    /**
     * Specify fields that are searchable
     */
    protected $fieldSearchable = [
        {{searchableFields}}
    ];

    /**
     * Specify Model class name
     */
    public function model()
    {
        return {{model}}::class;
    }

    {{presenterMethod}}

    {{validatorMethod}}

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
        {{bootMethods}}
    }

    {{customMethods}}

    /**
     * Get active records
     */
    public function getActive()
    {
        return $this->findWhere(['status' => 'active']);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count(),
            'active' => $this->countWhere(['status' => 'active']),
            'created_today' => $this->countWhere([
                ['created_at', '>=', now()->startOfDay()]
            ]),
        ];
    }
}
```

### Template Variables

```php
// Available template variables:
{{namespace}}           // Repository namespace
{{class}}              // Repository class name
{{model}}              // Model class name
{{modelNamespace}}     // Full model namespace
{{presenterNamespace}} // Presenter namespace
{{validatorNamespace}} // Validator namespace
{{searchableFields}}   // Generated searchable fields
{{customMethods}}      // Custom methods if specified
{{description}}        // Class description
{{author}}             // Author name from config
{{version}}            // Version from config
{{cacheMinutes}}       // Cache duration
{{cacheEnabled}}       // Cache enabled flag
```

### Advanced Template Customization

```bash
# Generate with custom template
php artisan make:repository UserRepository --template=enhanced-api
```

**Enhanced API Template: `resources/stubs/repository-enhanced-api.stub`**

```php
<?php

namespace {{namespace}};

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use {{modelNamespace}};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

/**
 * {{class}}
 * 
 * Enhanced API repository with caching, events, and monitoring
 * 
 * Features:
 * - Automatic caching with intelligent invalidation
 * - Event-driven architecture
 * - Performance monitoring
 * - Error handling and logging
 * - HashId support
 * - Search and filtering
 * 
 * @package {{package}}
 */
class {{class}} extends BaseRepository
{
    /**
     * Performance monitoring
     */
    protected $performanceTracking = true;
    protected $performanceThreshold = 1000; // ms

    /**
     * Cache configuration
     */
    protected $cacheMinutes = {{cacheMinutes}};
    protected $cacheEnabled = {{cacheEnabled}};
    protected $cacheTags = [
        '{{model_snake}}',
        '{{model_snake}}_repository',
    ];

    /**
     * Event configuration
     */
    protected $eventsEnabled = true;

    /**
     * Specify fields that are searchable
     */
    protected $fieldSearchable = [
        {{searchableFields}}
    ];

    /**
     * Specify Model class name
     */
    public function model()
    {
        return {{model}}::class;
    }

    {{presenterMethod}}

    {{validatorMethod}}

    /**
     * Boot up the repository
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
        $this->setupEventListeners();
        $this->initializeMonitoring();
    }

    /**
     * Enhanced create with monitoring and events
     */
    public function create(array $attributes)
    {
        $startTime = microtime(true);
        
        try {
            $this->fireEvent('creating', $attributes);
            
            $model = parent::create($attributes);
            
            $this->fireEvent('created', $model);
            $this->trackPerformance('create', $startTime);
            $this->invalidateCache(['{{model_snake}}', 'created']);
            
            return $model;
            
        } catch (\Exception $e) {
            $this->logError('create', $e, $attributes);
            throw $e;
        }
    }

    /**
     * Enhanced update with monitoring and events
     */
    public function update(array $attributes, $id)
    {
        $startTime = microtime(true);
        
        try {
            $this->fireEvent('updating', $attributes, $id);
            
            $model = parent::update($attributes, $id);
            
            $this->fireEvent('updated', $model);
            $this->trackPerformance('update', $startTime);
            $this->invalidateCache(['{{model_snake}}', "{{model_snake}}:{$id}", 'updated']);
            
            return $model;
            
        } catch (\Exception $e) {
            $this->logError('update', $e, $attributes, $id);
            throw $e;
        }
    }

    /**
     * Enhanced delete with monitoring and events
     */
    public function delete($id)
    {
        $startTime = microtime(true);
        
        try {
            $model = $this->find($id);
            $this->fireEvent('deleting', $model);
            
            $result = parent::delete($id);
            
            $this->fireEvent('deleted', $model);
            $this->trackPerformance('delete', $startTime);
            $this->invalidateCache(['{{model_snake}}', "{{model_snake}}:{$id}", 'deleted']);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logError('delete', $e, null, $id);
            throw $e;
        }
    }

    {{customMethods}}

    /**
     * Get with intelligent caching
     */
    public function getCached($key, $callback, $tags = [])
    {
        $cacheKey = "{{model_snake}}:{$key}";
        $cacheTags = array_merge($this->cacheTags, $tags);
        
        return Cache::tags($cacheTags)->remember($cacheKey, $this->cacheMinutes, $callback);
    }

    /**
     * Setup event listeners
     */
    protected function setupEventListeners()
    {
        if (!$this->eventsEnabled) {
            return;
        }

        Event::listen('{{model_snake}}.cache.clear', function($tags) {
            $this->invalidateCache($tags);
        });
    }

    /**
     * Fire repository event
     */
    protected function fireEvent($action, $data, $id = null)
    {
        if (!$this->eventsEnabled) {
            return;
        }

        Event::fire("{{model_snake}}.repository.{$action}", [
            'repository' => static::class,
            'action' => $action,
            'data' => $data,
            'id' => $id,
            'timestamp' => now(),
        ]);
    }

    /**
     * Track performance metrics
     */
    protected function trackPerformance($operation, $startTime)
    {
        if (!$this->performanceTracking) {
            return;
        }

        $duration = (microtime(true) - $startTime) * 1000;

        if ($duration > $this->performanceThreshold) {
            Log::warning("Slow repository operation detected", [
                'repository' => static::class,
                'operation' => $operation,
                'duration_ms' => $duration,
                'threshold_ms' => $this->performanceThreshold,
            ]);
        }

        // Send to monitoring service
        if (app()->bound('monitoring')) {
            app('monitoring')->timing("repository.{{model_snake}}.{$operation}", $duration);
        }
    }

    /**
     * Log errors with context
     */
    protected function logError($operation, \Exception $e, $data = null, $id = null)
    {
        Log::error("Repository operation failed", [
            'repository' => static::class,
            'operation' => $operation,
            'error' => $e->getMessage(),
            'data' => $data,
            'id' => $id,
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Invalidate cache by tags
     */
    protected function invalidateCache(array $tags)
    {
        Cache::tags(array_merge($this->cacheTags, $tags))->flush();
    }

    /**
     * Initialize monitoring
     */
    protected function initializeMonitoring()
    {
        if (app()->bound('monitoring')) {
            app('monitoring')->increment("repository.{{model_snake}}.initialized");
        }
    }
}
```

## ⚡ Advanced Scaffolding

### Microservice Generation

```bash
# Generate complete microservice structure
php artisan make:microservice UserService \
    --entities=User,Profile,Role \
    --with-docker \
    --with-tests \
    --with-docs
```

**Generated Structure:**
```
app/Services/UserService/
├── Models/
│   ├── User.php
│   ├── Profile.php
│   └── Role.php
├── Repositories/
│   ├── UserRepository.php
│   ├── ProfileRepository.php
│   └── RoleRepository.php
├── Controllers/
│   └── Api/
├── Presenters/
├── Transformers/
├── Validators/
├── Events/
├── Listeners/
├── Jobs/
├── Tests/
├── routes/
├── config/
├── docker/
└── docs/
```

### Module Generation

```bash
# Generate modular structure
php artisan make:module Blog \
    --entities=Post,Comment,Category,Tag \
    --with-admin \
    --with-frontend \
    --with-api
```

### Multi-Tenant Generation

```bash
# Generate tenant-aware entities
php artisan make:tenant-entity Company \
    --tenant-field=company_id \
    --with-scopes \
    --with-middleware
```

**Generated Tenant-Aware Repository:**

```php
class CompanyRepository extends BaseRepository
{
    protected $tenantField = 'company_id';
    protected $tenantScope = true;

    public function boot()
    {
        parent::boot();
        
        if ($this->tenantScope && auth()->check()) {
            $this->pushCriteria(new TenantCriteria(auth()->user()->company_id));
        }
    }

    protected function applyTenantScope($query)
    {
        if ($this->tenantScope && auth()->check()) {
            return $query->where($this->tenantField, auth()->user()->company_id);
        }
        
        return $query;
    }
}
```

## ⚙️ Configuration & Customization

### Generator Configuration

```php
// config/repository.php - Generator settings
return [
    'generator' => [
        'basePath' => app_path(),
        'rootNamespace' => 'App\\',
        'stubsOverridePath' => app_path(),
        
        'paths' => [
            'models' => 'Models',
            'repositories' => 'Repositories',
            'interfaces' => 'Repositories/Contracts',
            'criteria' => 'Criteria',
            'transformers' => 'Transformers',
            'presenters' => 'Presenters',
            'validators' => 'Validators',
            'controllers' => 'Http/Controllers',
            'provider' => 'RepositoryServiceProvider',
            'requests' => 'Http/Requests',
            'tests' => 'Tests',
        ],

        'templates' => [
            'repository' => 'repository.stub',
            'criteria' => 'criteria.stub',
            'presenter' => 'presenter.stub',
            'transformer' => 'transformer.stub',
            'validator' => 'validator.stub',
            'controller' => 'controller.stub',
            'request' => 'request.stub',
            'test' => 'test.stub',
        ],

        'defaults' => [
            'cache_enabled' => true,
            'cache_minutes' => 60,
            'events_enabled' => true,
            'validation_enabled' => true,
            'presenter_enabled' => true,
            'include_relationships' => true,
            'include_timestamps' => true,
            'include_soft_deletes' => false,
        ],

        'features' => [
            'hashid_support' => true,
            'search_fields' => true,
            'api_docs' => true,
            'test_generation' => true,
            'factory_generation' => true,
            'seeder_generation' => true,
        ],
    ],
];
```

### Custom Generator Commands

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Custom generator for specific project patterns
 */
class MakeServiceCommand extends Command
{
    protected $signature = 'make:service {name} {--api} {--admin} {--frontend}';
    protected $description = 'Generate a complete service with repositories, controllers, and views';

    public function handle()
    {
        $name = $this->argument('name');
        
        $this->info("Generating service: {$name}");
        
        // Generate base entity
        $this->call('make:entity', ['name' => $name]);
        
        // Generate API controller if requested
        if ($this->option('api')) {
            $this->call('make:controller', [
                'name' => "Api/{$name}Controller",
                '--api' => true,
                '--model' => $name,
            ]);
        }
        
        // Generate admin controller if requested
        if ($this->option('admin')) {
            $this->call('make:controller', [
                'name' => "Admin/{$name}Controller",
                '--resource' => true,
                '--model' => $name,
            ]);
        }
        
        // Generate frontend views if requested
        if ($this->option('frontend')) {
            $this->generateViews($name);
        }
        
        $this->info("Service {$name} generated successfully!");
    }
    
    protected function generateViews($name)
    {
        $views = ['index', 'show', 'create', 'edit'];
        $viewPath = resource_path("views/" . Str::kebab($name));
        
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }
        
        foreach ($views as $view) {
            $content = $this->generateViewContent($name, $view);
            file_put_contents("{$viewPath}/{$view}.blade.php", $content);
        }
    }
    
    protected function generateViewContent($name, $view): string
    {
        // Generate view content based on template
        return view("stubs.views.{$view}", compact('name'))->render();
    }
}
```

## 💡 Best Practices

### Generator Workflow

```bash
# 1. Plan your entities and relationships
# 2. Generate models and migrations first
php artisan make:entity User --fillable=name,email,password

# 3. Generate relationships
php artisan make:entity Post --relations=user:belongsTo,comments:hasMany

# 4. Generate API layer
php artisan make:api-resource Post --full-crud

# 5. Generate tests
php artisan make:repository-tests PostRepository --feature --unit

# 6. Generate documentation
php artisan make:api-docs Post
```

### Code Quality Standards

```bash
# Generate with code quality tools
php artisan make:entity User \
    --with-phpstan \
    --with-phpcs \
    --with-phpunit \
    --with-pest
```

### Team Collaboration

```bash
# Generate with team standards
php artisan make:entity Post \
    --author="Team Lead" \
    --version="1.0.0" \
    --namespace="App\\Blog" \
    --description="Blog post entity with full CRUD operations"
```

### Performance Optimization

```bash
# Generate with performance features
php artisan make:entity User \
    --with-cache \
    --with-eager-loading \
    --with-query-optimization \
    --with-monitoring
```

---

**Next:** Learn about **[Performance Guide](performance.md)** for optimization techniques and monitoring.