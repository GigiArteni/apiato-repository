# API Examples - Real-World Usage Patterns

Comprehensive collection of real-world API examples using Apiato Repository, from basic CRUD operations to complex enterprise patterns with automatic HashId support and performance optimization.

## 📚 Table of Contents

- [Basic API Patterns](#-basic-api-patterns)
- [Advanced Search & Filtering](#-advanced-search--filtering)
- [Relationship Management](#-relationship-management)
- [File Upload & Media](#-file-upload--media)
- [Authentication & Authorization](#-authentication--authorization)
- [E-commerce Examples](#-e-commerce-examples)
- [Real-Time Features](#-real-time-features)
- [Enterprise Patterns](#-enterprise-patterns)

## 🔧 Basic API Patterns

### Simple CRUD API

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

/**
 * User API Controller
 * All HashId operations automatic, caching enabled by default
 */
class UserController extends Controller
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @api {get} /api/users Get Users
     * @apiName GetUsers
     * @apiGroup Users
     * @apiParam {String} [search] Search users by name or email
     * @apiParam {String} [status] Filter by status (active,inactive,suspended)
     * @apiParam {String} [role_id] Filter by role HashId
     * @apiParam {String} [orderBy] Sort field (name,email,created_at)
     * @apiParam {String} [sortedBy] Sort direction (asc,desc)
     * @apiParam {String} [with] Include relationships (profile,roles,posts)
     * @apiParam {Number} [per_page] Items per page (default: 15)
     */
    public function index(Request $request)
    {
        $users = $this->repository->paginate($request->get('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * @api {post} /api/users Create User
     * @apiName CreateUser
     * @apiGroup Users
     */
    public function store(CreateUserRequest $request)
    {
        try {
            $user = $this->repository->create($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully.',
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @api {get} /api/users/:id Get User
     * @apiName GetUser
     * @apiGroup Users
     * @apiParam {String} id User HashId
     */
    public function show($id)
    {
        try {
            // HashId automatically decoded by repository
            $user = $this->repository->find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @api {put} /api/users/:id Update User
     * @apiName UpdateUser
     * @apiGroup Users
     */
    public function update(UpdateUserRequest $request, $id)
    {
        try {
            $user = $this->repository->update($request->validated(), $id);
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully.',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * @api {delete} /api/users/:id Delete User
     * @apiName DeleteUser
     * @apiGroup Users
     */
    public function destroy($id)
    {
        try {
            $deleted = $this->repository->delete($id);
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
```

### API Usage Examples

```bash
# Get all users with automatic caching
GET /api/users
{
  "success": true,
  "data": {
    "data": [
      {
        "id": "gY6N8",
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2024-06-03T10:30:00Z"
      }
    ],
    "meta": {
      "total": 150,
      "per_page": 15,
      "current_page": 1,
      "last_page": 10
    }
  }
}

# Search users by name or email
GET /api/users?search=john
GET /api/users?search=name:john;email:john@example.com

# Filter by status and role (HashId)
GET /api/users?filter=status:active;role_id:admin_role_hashid

# Include relationships
GET /api/users?with=profile,roles,posts

# Sorting and pagination
GET /api/users?orderBy=created_at&sortedBy=desc&per_page=25

# Complex filtering with search
GET /api/users?search=name:like:john&filter=status:active&orderBy=name&with=profile
```

### Bulk Operations API

```php
class UserBulkController extends Controller
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @api {post} /api/users/bulk Bulk Operations
     * @apiName BulkUsers
     * @apiGroup Users
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:create,update,delete,activate,deactivate',
            'data' => 'required|array',
            'data.*.id' => 'required_if:action,update,delete,activate,deactivate',
        ]);

        $action = $request->get('action');
        $data = $request->get('data');
        $results = [];

        switch ($action) {
            case 'create':
                $results = $this->bulkCreate($data);
                break;
            case 'update':
                $results = $this->bulkUpdate($data);
                break;
            case 'delete':
                $results = $this->bulkDelete($data);
                break;
            case 'activate':
                $results = $this->bulkActivate($data);
                break;
            case 'deactivate':
                $results = $this->bulkDeactivate($data);
                break;
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'processed' => count($data),
            'results' => $results,
        ]);
    }

    protected function bulkCreate(array $records): array
    {
        $created = [];
        $errors = [];

        foreach ($records as $index => $data) {
            try {
                $user = $this->repository->create($data);
                $created[] = $user;
            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        return [
            'created' => $created,
            'errors' => $errors,
            'success_count' => count($created),
            'error_count' => count($errors),
        ];
    }

    protected function bulkUpdate(array $records): array
    {
        $updated = [];
        $errors = [];

        foreach ($records as $index => $data) {
            try {
                $id = $data['id'];
                unset($data['id']);
                
                $user = $this->repository->update($data, $id);
                $updated[] = $user;
            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        return [
            'updated' => $updated,
            'errors' => $errors,
            'success_count' => count($updated),
            'error_count' => count($errors),
        ];
    }

    protected function bulkDelete(array $records): array
    {
        $deleted = [];
        $errors = [];

        foreach ($records as $index => $data) {
            try {
                $this->repository->delete($data['id']);
                $deleted[] = $data['id'];
            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors,
            'success_count' => count($deleted),
            'error_count' => count($errors),
        ];
    }
}
```

## 🔍 Advanced Search & Filtering

### Advanced Search API

```php
class UserSearchController extends Controller
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @api {get} /api/users/search Advanced Search
     * @apiName AdvancedSearchUsers
     * @apiGroup Users
     */
    public function search(Request $request)
    {
        $this->repository->pushCriteria(app(RequestCriteria::class));

        // Add advanced search criteria
        if ($request->has('q')) {
            $this->repository->pushCriteria(new FullTextSearchCriteria($request->get('q')));
        }

        if ($request->has('location')) {
            $this->repository->pushCriteria(new LocationCriteria($request->get('location')));
        }

        if ($request->has('age_range')) {
            $this->repository->pushCriteria(new AgeRangeCriteria($request->get('age_range')));
        }

        if ($request->has('skills')) {
            $this->repository->pushCriteria(new SkillsCriteria($request->get('skills')));
        }

        $users = $this->repository->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users,
            'query' => $request->all(),
            'filters_applied' => $this->getAppliedFilters($request),
        ]);
    }

    /**
     * @api {post} /api/users/advanced-search Complex Search
     * @apiName ComplexSearchUsers
     * @apiGroup Users
     */
    public function advancedSearch(Request $request)
    {
        $request->validate([
            'filters' => 'required|array',
            'sort' => 'array',
            'include' => 'array',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $filters = $request->get('filters', []);
        $sort = $request->get('sort', []);
        $include = $request->get('include', []);

        // Build complex query
        $users = $this->repository
            ->with($include)
            ->scopeQuery(function($query) use ($filters, $sort) {
                // Apply filters
                foreach ($filters as $filter) {
                    $query = $this->applyAdvancedFilter($query, $filter);
                }

                // Apply sorting
                foreach ($sort as $sortItem) {
                    $query->orderBy($sortItem['field'], $sortItem['direction'] ?? 'asc');
                }

                return $query;
            })
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users,
            'search_meta' => [
                'filters_count' => count($filters),
                'sort_count' => count($sort),
                'includes' => $include,
            ],
        ]);
    }

    protected function applyAdvancedFilter($query, array $filter)
    {
        $field = $filter['field'];
        $operator = $filter['operator'] ?? '=';
        $value = $filter['value'];

        switch ($operator) {
            case 'equals':
                return $query->where($field, '=', $value);
            case 'not_equals':
                return $query->where($field, '!=', $value);
            case 'contains':
                return $query->where($field, 'like', "%{$value}%");
            case 'starts_with':
                return $query->where($field, 'like', "{$value}%");
            case 'ends_with':
                return $query->where($field, 'like', "%{$value}");
            case 'in':
                return $query->whereIn($field, (array) $value);
            case 'not_in':
                return $query->whereNotIn($field, (array) $value);
            case 'between':
                return $query->whereBetween($field, $value);
            case 'greater_than':
                return $query->where($field, '>', $value);
            case 'less_than':
                return $query->where($field, '<', $value);
            case 'is_null':
                return $query->whereNull($field);
            case 'is_not_null':
                return $query->whereNotNull($field);
            default:
                return $query;
        }
    }

    /**
     * @api {get} /api/users/faceted-search Faceted Search
     * @apiName FacetedSearchUsers
     * @apiGroup Users
     */
    public function facetedSearch(Request $request)
    {
        // Get faceted search results
        $facets = $this->getFacets($request);
        $users = $this->getFilteredUsers($request);

        return response()->json([
            'success' => true,
            'data' => $users,
            'facets' => $facets,
            'total_without_filters' => $this->repository->count(),
        ]);
    }

    protected function getFacets(Request $request): array
    {
        // Build base query without current filters
        $baseQuery = $this->repository->getModel()->newQuery();

        return [
            'status' => $this->getFacetCounts($baseQuery, 'status'),
            'role' => $this->getFacetCounts($baseQuery, 'role_id'),
            'department' => $this->getFacetCounts($baseQuery, 'department_id'),
            'created_year' => $this->getDateFacetCounts($baseQuery, 'created_at', 'year'),
            'age_ranges' => $this->getAgeFacetCounts($baseQuery),
        ];
    }

    protected function getFacetCounts($query, $field): array
    {
        return $query->select($field, \DB::raw('count(*) as count'))
                    ->groupBy($field)
                    ->orderBy('count', 'desc')
                    ->get()
                    ->toArray();
    }
}
```

### Search API Usage Examples

```bash
# Full-text search
GET /api/users/search?q=john+developer

# Location-based search
GET /api/users/search?location=san+francisco&radius=50km

# Skills and experience search
GET /api/users/search?skills=laravel,php,vue&experience=5+

# Age range search
GET /api/users/search?age_range=25-35

# Complex POST search
POST /api/users/advanced-search
{
  "filters": [
    {
      "field": "status",
      "operator": "equals",
      "value": "active"
    },
    {
      "field": "name",
      "operator": "contains",
      "value": "john"
    },
    {
      "field": "created_at",
      "operator": "between",
      "value": ["2024-01-01", "2024-12-31"]
    }
  ],
  "sort": [
    {
      "field": "name",
      "direction": "asc"
    }
  ],
  "include": ["profile", "roles"],
  "per_page": 20
}

# Faceted search with filters
GET /api/users/faceted-search?status=active&role_id=admin_hashid
{
  "success": true,
  "data": { /* filtered users */ },
  "facets": {
    "status": [
      {"status": "active", "count": 150},
      {"status": "inactive", "count": 25}
    ],
    "role": [
      {"role_id": "admin_hash", "count": 10},
      {"role_id": "user_hash", "count": 140}
    ]
  }
}
```

## 🔗 Relationship Management

### Nested Resource APIs

```php
class PostController extends Controller
{
    protected PostRepository $postRepository;
    protected CommentRepository $commentRepository;

    public function __construct(
        PostRepository $postRepository,
        CommentRepository $commentRepository
    ) {
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
    }

    /**
     * @api {get} /api/users/:userId/posts Get User Posts
     * @apiName GetUserPosts
     * @apiGroup Posts
     */
    public function userPosts($userId, Request $request)
    {
        // HashId automatically decoded
        $posts = $this->postRepository
            ->with(['tags', 'category'])
            ->findWhere(['user_id' => $userId]);

        return response()->json([
            'success' => true,
            'data' => $posts,
            'user_id' => $userId,
        ]);
    }

    /**
     * @api {get} /api/posts/:postId/comments Get Post Comments
     * @apiName GetPostComments
     * @apiGroup Comments
     */
    public function postComments($postId, Request $request)
    {
        $comments = $this->commentRepository
            ->with(['user:id,name,avatar'])
            ->scopeQuery(function($query) use ($postId) {
                return $query->where('post_id', $postId)
                            ->where('status', 'approved')
                            ->orderBy('created_at', 'desc');
            })
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $comments,
            'post_id' => $postId,
        ]);
    }

    /**
     * @api {post} /api/posts/:postId/comments Create Comment
     * @apiName CreateComment
     * @apiGroup Comments
     */
    public function createComment(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $this->commentRepository->create([
            'content' => $request->get('content'),
            'post_id' => $postId, // HashId decoded automatically
            'user_id' => auth()->id(),
            'parent_id' => $request->get('parent_id'),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Comment created successfully.',
        ], 201);
    }

    /**
     * @api {post} /api/posts/:postId/tags Attach Tags
     * @apiName AttachTags
     * @apiGroup Posts
     */
    public function attachTags(Request $request, $postId)
    {
        $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'required|string', // HashIds
        ]);

        $post = $this->postRepository->find($postId);
        
        // Decode HashIds automatically
        $tagIds = collect($request->get('tag_ids'))
            ->map(function($hashId) {
                return hashid_decode($hashId);
            })
            ->filter();

        $post->tags()->syncWithoutDetaching($tagIds);

        // Reload with tags
        $post = $this->postRepository->with(['tags'])->find($postId);

        return response()->json([
            'success' => true,
            'data' => $post,
            'message' => 'Tags attached successfully.',
        ]);
    }

    /**
     * @api {delete} /api/posts/:postId/tags/:tagId Detach Tag
     * @apiName DetachTag
     * @apiGroup Posts
     */
    public function detachTag($postId, $tagId)
    {
        $post = $this->postRepository->find($postId);
        
        // HashId decoded automatically
        $post->tags()->detach($tagId);

        return response()->json([
            'success' => true,
            'message' => 'Tag detached successfully.',
        ]);
    }
}
```

### Many-to-Many Relationship APIs

```php
class UserRoleController extends Controller
{
    protected UserRepository $userRepository;
    protected RoleRepository $roleRepository;

    /**
     * @api {get} /api/users/:userId/roles Get User Roles
     * @apiName GetUserRoles
     * @apiGroup UserRoles
     */
    public function getUserRoles($userId)
    {
        $user = $this->userRepository->with(['roles.permissions'])->find($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'roles' => $user->roles,
                'permissions' => $user->getAllPermissions(),
            ],
        ]);
    }

    /**
     * @api {post} /api/users/:userId/roles Assign Roles
     * @apiName AssignRoles
     * @apiGroup UserRoles
     */
    public function assignRoles(Request $request, $userId)
    {
        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'required|string', // HashIds
        ]);

        $user = $this->userRepository->find($userId);
        
        // Decode HashIds for roles
        $roleIds = collect($request->get('role_ids'))
            ->map(fn($hashId) => hashid_decode($hashId))
            ->filter();

        $user->roles()->sync($roleIds);

        // Clear user permissions cache
        Cache::forget("user_permissions_{$user->id}");

        return response()->json([
            'success' => true,
            'message' => 'Roles assigned successfully.',
            'data' => $this->userRepository->with(['roles'])->find($userId),
        ]);
    }

    /**
     * @api {get} /api/roles/:roleId/users Get Role Users
     * @apiName GetRoleUsers
     * @apiGroup UserRoles
     */
    public function getRoleUsers($roleId, Request $request)
    {
        $users = $this->userRepository
            ->with(['profile:id,user_id,avatar,bio'])
            ->scopeQuery(function($query) use ($roleId) {
                return $query->whereHas('roles', function($q) use ($roleId) {
                    $q->where('id', $roleId); // HashId decoded automatically
                });
            })
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users,
            'role_id' => $roleId,
        ]);
    }
}
```

## 📁 File Upload & Media

### File Upload API

```php
class MediaController extends Controller
{
    protected MediaRepository $repository;

    public function __construct(MediaRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @api {post} /api/media/upload Upload File
     * @apiName UploadFile
     * @apiGroup Media
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'type' => 'required|in:image,document,video,audio',
            'folder' => 'nullable|string',
            'alt_text' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $file = $request->file('file');
            $type = $request->get('type');
            $folder = $request->get('folder', 'uploads');

            // Validate file type
            $this->validateFileType($file, $type);

            // Store file
            $path = $file->store("{$folder}/{$type}/" . date('Y/m'), 'public');
            
            // Create media record
            $media = $this->repository->create([
                'filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'type' => $type,
                'folder' => $folder,
                'alt_text' => $request->get('alt_text'),
                'title' => $request->get('title'),
                'user_id' => auth()->id(),
                'metadata' => $this->extractMetadata($file, $type),
            ]);

            // Generate thumbnails for images
            if ($type === 'image') {
                $this->generateThumbnails($media);
            }

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'File uploaded successfully.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @api {post} /api/media/upload-multiple Upload Multiple Files
     * @apiName UploadMultipleFiles
     * @apiGroup Media
     */
    public function uploadMultiple(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:10240',
            'type' => 'required|in:image,document,video,audio',
            'folder' => 'nullable|string',
        ]);

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $index => $file) {
            try {
                $path = $file->store($request->get('folder', 'uploads') . '/' . date('Y/m'), 'public');
                
                $media = $this->repository->create([
                    'filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'type' => $request->get('type'),
                    'user_id' => auth()->id(),
                    'metadata' => $this->extractMetadata($file, $request->get('type')),
                ]);

                $uploadedFiles[] = $media;

            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $uploadedFiles,
            'uploaded_count' => count($uploadedFiles),
            'errors' => $errors,
        ]);
    }

    /**
     * @api {get} /api/media Get Media Files
     * @apiName GetMedia
     * @apiGroup Media
     */
    public function index(Request $request)
    {
        $this->repository->pushCriteria(app(RequestCriteria::class));

        // Add media-specific criteria
        if ($request->has('type')) {
            $this->repository->pushCriteria(new MediaTypeCriteria($request->get('type')));
        }

        if ($request->has('folder')) {
            $this->repository->pushCriteria(new MediaFolderCriteria($request->get('folder')));
        }

        $media = $this->repository->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    /**
     * @api {delete} /api/media/:id Delete Media
     * @apiName DeleteMedia
     * @apiGroup Media
     */
    public function destroy($id)
    {
        try {
            $media = $this->repository->find($id);
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found.',
                ], 404);
            }

            // Delete physical file
            Storage::disk('public')->delete($media->file_path);
            
            // Delete thumbnails if they exist
            if ($media->thumbnails) {
                foreach ($media->thumbnails as $thumbnail) {
                    Storage::disk('public')->delete($thumbnail);
                }
            }

            // Delete database record
            $this->repository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function validateFileType($file, $type)
    {
        $allowedTypes = [
            'image' => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac', 'flac'],
        ];

        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes[$type])) {
            throw new \InvalidArgumentException("File type {$extension} is not allowed for {$type} files.");
        }
    }

    protected function extractMetadata($file, $type): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'size_human' => $this->formatFileSize($file->getSize()),
        ];

        if ($type === 'image' && extension_loaded('exif')) {
            try {
                $exifData = exif_read_data($file->getRealPath());
                if ($exifData) {
                    $metadata['exif'] = [
                        'width' => $exifData['COMPUTED']['Width'] ?? null,
                        'height' => $exifData['COMPUTED']['Height'] ?? null,
                        'camera' => $exifData['Model'] ?? null,
                        'date_taken' => $exifData['DateTime'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                // Ignore exif errors
            }
        }

        return $metadata;
    }

    protected function generateThumbnails($media)
    {
        // Generate different sized thumbnails
        $sizes = [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600],
        ];

        $thumbnails = [];
        
        foreach ($sizes as $size => [$width, $height]) {
            try {
                $thumbnailPath = $this->createThumbnail($media->file_path, $width, $height, $size);
                $thumbnails[$size] = $thumbnailPath;
            } catch (\Exception $e) {
                Log::error("Failed to generate {$size} thumbnail for media {$media->id}: " . $e->getMessage());
            }
        }

        if (!empty($thumbnails)) {
            $this->repository->update(['thumbnails' => $thumbnails], $media->id);
        }
    }

    protected function formatFileSize($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

## 🔐 Authentication & Authorization

### Auth API with Repository Pattern

```php
class AuthController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @api {post} /api/auth/register Register User
     * @apiName RegisterUser
     * @apiGroup Auth
     */
    public function register(RegisterRequest $request)
    {
        try {
            $userData = $request->validated();
            $userData['password'] = Hash::make($userData['password']);
            $userData['status'] = 'pending';
            
            $user = $this->userRepository->create($userData);
            
            // Generate verification token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Send verification email
            event(new UserRegistered($user));
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
                'message' => 'Registration successful. Please verify your email.',
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @api {post} /api/auth/login Login User
     * @apiName LoginUser
     * @apiGroup Auth
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        
        // Find user by email
        $user = $this->userRepository->findByField('email', $credentials['email'])->first();
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }
        
        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }
        
        // Update last login
        $this->userRepository->update([
            'last_login_at' => now(),
            'last_ip' => $request->ip(),
        ], $user->id);
        
        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'Login successful.',
        ]);
    }

    /**
     * @api {post} /api/auth/logout Logout User
     * @apiName LogoutUser
     * @apiGroup Auth
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * @api {get} /api/auth/me Get Current User
     * @apiName GetCurrentUser
     * @apiGroup Auth
     */
    public function me(Request $request)
    {
        $user = $this->userRepository
            ->with(['profile', 'roles.permissions'])
            ->find($request->user()->id);
            
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * @api {post} /api/auth/refresh Refresh Token
     * @apiName RefreshToken
     * @apiGroup Auth
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'Token refreshed successfully.',
        ]);
    }
}
```

### Permission-Based API Access

```php
class SecureController extends Controller
{
    protected $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
        
        // Apply permission middleware
        $this->middleware('permission:view-users')->only(['index', 'show']);
        $this->middleware('permission:create-users')->only(['store']);
        $this->middleware('permission:edit-users')->only(['update']);
        $this->middleware('permission:delete-users')->only(['destroy']);
    }

    /**
     * Get data based on user permissions
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Apply permission-based filtering
        if ($user->hasRole('admin')) {
            // Admins see everything
            $data = $this->repository->paginate(15);
        } elseif ($user->hasRole('manager')) {
            // Managers see their department
            $data = $this->repository->findWhere([
                'department_id' => $user->department_id
            ]);
        } else {
            // Regular users see only their own data
            $data = $this->repository->findWhere([
                'user_id' => $user->id
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Secure update with ownership check
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $record = $this->repository->find($id);
        
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        }
        
        // Check ownership or admin rights
        if ($record->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }
        
        $updated = $this->repository->update($request->validated(), $id);
        
        return response()->json([
            'success' => true,
            'data' => $updated,
            'message' => 'Updated successfully.',
        ]);
    }
}
```

## 🛒 E-commerce Examples

### Product Catalog API

```php
class ProductController extends Controller
{
    protected ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @api {get} /api/products Get Products
     * @apiName GetProducts
     * @apiGroup Products
     */
    public function index(Request $request)
    {
        $this->repository->pushCriteria(app(RequestCriteria::class));

        // Add e-commerce specific criteria
        if ($request->has('category_id')) {
            $this->repository->pushCriteria(new CategoryCriteria($request->get('category_id')));
        }

        if ($request->has('price_range')) {
            $this->repository->pushCriteria(new PriceRangeCriteria($request->get('price_range')));
        }

        if ($request->has('in_stock')) {
            $this->repository->pushCriteria(new InStockCriteria());
        }

        if ($request->has('brand_id')) {
            $this->repository->pushCriteria(new BrandCriteria($request->get('brand_id')));
        }

        $products = $this->repository
            ->with(['category', 'brand', 'images', 'variants'])
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $products,
            'filters' => [
                'categories' => $this->getAvailableCategories(),
                'brands' => $this->getAvailableBrands(),
                'price_ranges' => $this->getPriceRanges(),
            ],
        ]);
    }

    /**
     * @api {get} /api/products/:id Get Product Details
     * @apiName GetProduct
     * @apiGroup Products
     */
    public function show($id, Request $request)
    {
        $product = $this->repository
            ->with([
                'category',
                'brand', 
                'images',
                'variants.options',
                'reviews.user:id,name',
                'related_products' => function($query) {
                    $query->limit(8);
                }
            ])
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Track product view
        event(new ProductViewed($product, auth()->user()));

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'availability' => $this->getProductAvailability($product),
                'pricing' => $this->getProductPricing($product),
                'shipping' => $this->getShippingOptions($product),
                'recommendations' => $this->getRecommendations($product),
            ],
        ]);
    }

    protected function getProductAvailability($product): array
    {
        return [
            'in_stock' => $product->stock_quantity > 0,
            'stock_quantity' => $product->stock_quantity,
            'low_stock_threshold' => $product->low_stock_threshold,
            'is_low_stock' => $product->stock_quantity <= $product->low_stock_threshold,
            'estimated_delivery' => $this->calculateDeliveryDate($product),
        ];
    }

    protected function getProductPricing($product): array
    {
        $pricing = [
            'regular_price' => $product->regular_price,
            'sale_price' => $product->sale_price,
            'is_on_sale' => $product->sale_price && $product->sale_price < $product->regular_price,
            'discount_amount' => 0,
            'discount_percentage' => 0,
        ];

        if ($pricing['is_on_sale']) {
            $pricing['discount_amount'] = $product->regular_price - $product->sale_price;
            $pricing['discount_percentage'] = round(($pricing['discount_amount'] / $product->regular_price) * 100);
        }

        return $pricing;
    }
}
```

### Shopping Cart API

```php
class CartController extends Controller
{
    protected CartRepository $cartRepository;
    protected ProductRepository $productRepository;

    public function __construct(
        CartRepository $cartRepository,
        ProductRepository $productRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @api {get} /api/cart Get Cart
     * @apiName GetCart
     * @apiGroup Cart
     */
    public function index()
    {
        $userId = auth()->id();
        
        $cart = $this->cartRepository
            ->with(['items.product', 'items.variant'])
            ->findWhere(['user_id' => $userId])
            ->first();

        if (!$cart) {
            $cart = $this->cartRepository->create(['user_id' => $userId]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $cart,
                'summary' => $this->getCartSummary($cart),
                'shipping_options' => $this->getShippingOptions($cart),
            ],
        ]);
    }

    /**
     * @api {post} /api/cart/items Add Item to Cart
     * @apiName AddCartItem
     * @apiGroup Cart
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string', // HashId
            'variant_id' => 'nullable|string', // HashId
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $userId = auth()->id();
        $productId = $request->get('product_id'); // Auto-decoded
        $variantId = $request->get('variant_id'); // Auto-decoded
        $quantity = $request->get('quantity');

        // Verify product exists and is available
        $product = $this->productRepository->find($productId);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        if ($product->stock_quantity < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock.',
                'available_quantity' => $product->stock_quantity,
            ], 400);
        }

        // Get or create cart
        $cart = $this->cartRepository
            ->findWhere(['user_id' => $userId])
            ->first();

        if (!$cart) {
            $cart = $this->cartRepository->create(['user_id' => $userId]);
        }

        // Check if item already exists in cart
        $existingItem = $cart->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem->quantity + $quantity;
            
            if ($product->stock_quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add more items. Insufficient stock.',
                    'current_cart_quantity' => $existingItem->quantity,
                    'available_quantity' => $product->stock_quantity,
                ], 400);
            }

            $existingItem->update(['quantity' => $newQuantity]);
            $cartItem = $existingItem;
        } else {
            // Create new cart item
            $cartItem = $cart->items()->create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $product->sale_price ?: $product->regular_price,
            ]);
        }

        // Reload cart with items
        $cart = $this->cartRepository
            ->with(['items.product', 'items.variant'])
            ->find($cart->id);

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $cart,
                'added_item' => $cartItem,
                'summary' => $this->getCartSummary($cart),
            ],
            'message' => 'Item added to cart successfully.',
        ]);
    }

    /**
     * @api {put} /api/cart/items/:id Update Cart Item
     * @apiName UpdateCartItem
     * @apiGroup Cart
     */
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $cartItem = CartItem::findOrFail($id);
        $quantity = $request->get('quantity');

        // Verify ownership
        if ($cartItem->cart->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($quantity === 0) {
            // Remove item
            $cartItem->delete();
            $message = 'Item removed from cart.';
        } else {
            // Check stock
            if ($cartItem->product->stock_quantity < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock.',
                    'available_quantity' => $cartItem->product->stock_quantity,
                ], 400);
            }

            $cartItem->update(['quantity' => $quantity]);
            $message = 'Cart item updated successfully.';
        }

        // Reload cart
        $cart = $this->cartRepository
            ->with(['items.product', 'items.variant'])
            ->find($cartItem->cart_id);

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $cart,
                'summary' => $this->getCartSummary($cart),
            ],
            'message' => $message,
        ]);
    }

    protected function getCartSummary($cart): array
    {
        $items = $cart->items ?? collect();
        
        $subtotal = $items->sum(function($item) {
            return $item->quantity * $item->price;
        });

        $tax = $subtotal * 0.1; // 10% tax
        $shipping = $subtotal > 100 ? 0 : 10; // Free shipping over $100
        $total = $subtotal + $tax + $shipping;

        return [
            'items_count' => $items->sum('quantity'),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'currency' => 'USD',
        ];
    }
}
```

---

**Next:** Learn about **[Configuration](configuration.md)** for detailed setup and customization options.