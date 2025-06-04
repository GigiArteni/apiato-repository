# HashId Integration - Automatic ID Encoding

Complete guide to Apiato Repository's automatic HashId integration for secure, privacy-focused ID handling with zero configuration required.

## 📚 Table of Contents

- [Understanding HashId Integration](#-understanding-hashid-integration)
- [Automatic ID Processing](#-automatic-id-processing)
- [Configuration & Setup](#-configuration--setup)
- [Repository Integration](#-repository-integration)
- [API Usage Examples](#-api-usage-examples)
- [Custom HashId Strategies](#-custom-hashid-strategies)
- [Performance Optimization](#-performance-optimization)
- [Security Considerations](#-security-considerations)

## 🔐 Understanding HashId Integration

Apiato Repository automatically encodes/decodes IDs using HashIds, providing privacy and security benefits without exposing sequential database IDs in your APIs.

### Why Use HashIds?

```php
// Without HashIds (security risk)
GET /api/users/1      // Easy to guess other users: 2, 3, 4...
GET /api/posts/123    // Reveals database size and growth patterns

// With HashIds (secure and private)  
GET /api/users/gY6N8  // Impossible to guess other users
GET /api/posts/k2V9m  // No database information revealed
```

### Automatic Processing

```php
// All ID operations work automatically with HashIds
$user = $repository->find('gY6N8');           // HashId decoded automatically
$posts = $repository->findWhereIn('id', [     // Multiple HashIds
    'abc123', 'def456', 'ghi789'
]);
$userPosts = $repository->findWhere([         // HashIds in relationships
    'user_id' => 'gY6N8'                     // Decoded automatically
]);
```

## 🤖 Automatic ID Processing

### Input Processing (Decoding)

```php
class UserRepository extends BaseRepository
{
    // All these methods automatically decode HashIds:
    
    public function findUser($id)
    {
        // Works with both regular IDs and HashIds
        return $this->find($id);           // 'gY6N8' → 123 → User model
    }
    
    public function getUserPosts($userId)
    {
        // HashIds in where conditions are automatically decoded
        return $this->postRepository->findWhere([
            'user_id' => $userId           // 'gY6N8' → 123
        ]);
    }
    
    public function getMultipleUsers($ids)
    {
        // Arrays of HashIds are automatically decoded
        return $this->findWhereIn('id', $ids); // ['abc123', 'def456'] → [1, 2]
    }
    
    public function updateUser($id, array $data)
    {
        // Update operations work with HashIds
        return $this->update($data, $id);  // 'gY6N8' → update user 123
    }
    
    public function deleteUser($id)
    {
        // Delete operations work with HashIds
        return $this->delete($id);         // 'gY6N8' → delete user 123
    }
}
```

### Output Processing (Encoding)

```php
// Transformers automatically encode IDs in responses
class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id' => hashid_encode($user->id),          // 123 → 'gY6N8'
            'name' => $user->name,
            'department_id' => $user->department_id    // Auto-encoded if foreign key
                ? hashid_encode($user->department_id)
                : null,
            'role_ids' => $user->roles                 // Collection of IDs
                ->pluck('id')
                ->map(fn($id) => hashid_encode($id)),
        ];
    }
}

// API responses automatically contain HashIds
{
    "id": "gY6N8",
    "name": "John Doe",
    "department_id": "m3K9x",
    "role_ids": ["r1A2b", "r2B3c"],
    "created_at": "2024-06-03T10:30:00Z"
}
```

### Search & Filter Processing

```php
// RequestCriteria automatically handles HashIds in search parameters
// GET /api/users?search=department_id:m3K9x  → Decoded to actual department ID
// GET /api/posts?filter=user_id:gY6N8        → Decoded to actual user ID
// GET /api/users?search=id:in:abc123,def456  → Multiple HashIds decoded

class RequestCriteria
{
    public function apply($model, RepositoryInterface $repository)
    {
        // HashId fields are automatically detected and decoded
        if ($this->isHashIdField($field)) {
            $value = $repository->processIdValue($value);  // Automatic decoding
        }
        
        return $model->where($field, $condition, $value);
    }
}
```

## ⚙️ Configuration & Setup

### Basic Configuration

```php
// config/repository.php - HashId settings
return [
    'apiato' => [
        'hashid_enabled' => true,          // Enable HashId processing
        'hashid_length' => 6,              // Minimum HashId length
        'hashid_alphabet' => null,         // Custom alphabet (optional)
        'hashid_salt' => null,             // Custom salt (uses APP_KEY by default)
    ],
];

// .env configuration
HASHID_ENABLED=true
HASHID_LENGTH=6
HASHID_SALT="${APP_KEY}"
```

### Advanced Configuration

```php
// config/hashids.php - Detailed HashId configuration
return [
    'default' => [
        'salt' => env('HASHIDS_SALT', env('APP_KEY')),
        'length' => env('HASHIDS_LENGTH', 6),
        'alphabet' => env('HASHIDS_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
    ],
    
    // Multiple HashId connections for different use cases
    'connections' => [
        'users' => [
            'salt' => env('HASHIDS_USERS_SALT', env('APP_KEY') . '_users'),
            'length' => 8,
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
        ],
        
        'posts' => [
            'salt' => env('HASHIDS_POSTS_SALT', env('APP_KEY') . '_posts'),
            'length' => 6,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyz0123456789',
        ],
        
        'sensitive' => [
            'salt' => env('HASHIDS_SENSITIVE_SALT', env('APP_KEY') . '_sensitive'),
            'length' => 12,
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        ],
    ],
];
```

### Service Provider Registration

```php
// app/Providers/HashIdServiceProvider.php
class HashIdServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register default HashIds instance
        $this->app->singleton('hashids', function ($app) {
            return new \Hashids\Hashids(
                config('hashids.default.salt'),
                config('hashids.default.length'),
                config('hashids.default.alphabet')
            );
        });
        
        // Register multiple connections
        foreach (config('hashids.connections', []) as $name => $config) {
            $this->app->singleton("hashids.{$name}", function ($app) use ($config) {
                return new \Hashids\Hashids(
                    $config['salt'],
                    $config['length'],
                    $config['alphabet']
                );
            });
        }
    }
    
    public function boot()
    {
        // Register helper functions
        if (!function_exists('hashid_encode')) {
            function hashid_encode($id, $connection = 'default') {
                if ($connection === 'default') {
                    return app('hashids')->encode($id);
                }
                return app("hashids.{$connection}")->encode($id);
            }
        }
        
        if (!function_exists('hashid_decode')) {
            function hashid_decode($hashid, $connection = 'default') {
                if ($connection === 'default') {
                    $decoded = app('hashids')->decode($hashid);
                } else {
                    $decoded = app("hashids.{$connection}")->decode($hashid);
                }
                return !empty($decoded) ? $decoded[0] : null;
            }
        }
    }
}
```

## 🏗️ Repository Integration

### Basic Repository with HashIds

```php
class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }
    
    // All methods automatically handle HashIds - no changes needed!
    
    /**
     * Find user by HashId or regular ID
     */
    public function findUser($id)
    {
        // Works with: 123, "123", "gY6N8"
        return $this->find($id);
    }
    
    /**
     * Update user with HashId
     */
    public function updateUser($id, array $data)
    {
        // $id can be HashId - automatically decoded
        return $this->update($data, $id);
    }
    
    /**
     * Find users by role (supports HashId role_id)
     */
    public function findByRole($roleId)
    {
        // $roleId can be HashId - automatically decoded
        return $this->findWhere(['role_id' => $roleId]);
    }
}
```

### Advanced Repository with Custom HashId Logic

```php
class PostRepository extends BaseRepository
{
    // Use custom HashId connection for posts
    protected $hashIdConnection = 'posts';
    
    public function model()
    {
        return Post::class;
    }
    
    /**
     * Override ID processing for custom logic
     */
    protected function processIdValue($value)
    {
        // Use custom HashId connection
        if (is_string($value) && $this->looksLikeHashId($value)) {
            return hashid_decode($value, $this->hashIdConnection);
        }
        
        return parent::processIdValue($value);
    }
    
    /**
     * Find posts by multiple criteria with HashId support
     */
    public function findAdvanced(array $criteria)
    {
        // All HashId fields automatically processed
        return $this->findWhere([
            'user_id' => $criteria['user_id'],      // HashId → ID
            'category_id' => $criteria['category_id'], // HashId → ID
            'status' => $criteria['status'],         // Regular field
        ]);
    }
    
    /**
     * Get user's posts with HashId user ID
     */
    public function getUserPosts($userId, $limit = 10)
    {
        // $userId can be HashId - automatically decoded
        return $this->scopeQuery(function($query) use ($userId) {
            return $query->where('user_id', $userId)
                        ->orderBy('created_at', 'desc');
        })->paginate($limit);
    }
}
```

### Model Integration

```php
class User extends Model
{
    // Optional: Add HashId methods to models
    
    /**
     * Get the HashId for this model
     */
    public function getHashIdAttribute(): string
    {
        return hashid_encode($this->id, 'users');
    }
    
    /**
     * Find model by HashId
     */
    public static function findByHashId($hashId): ?self
    {
        $id = hashid_decode($hashId, 'users');
        return $id ? static::find($id) : null;
    }
    
    /**
     * Route model binding with HashId support
     */
    public function getRouteKeyName(): string
    {
        return 'hash_id'; // Use HashId for route binding
    }
    
    public function resolveRouteBinding($value, $field = null)
    {
        $id = hashid_decode($value, 'users');
        return $id ? $this->where('id', $id)->first() : null;
    }
    
    /**
     * JSON serialization with HashIds
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Replace ID with HashId
        if (isset($array['id'])) {
            $array['id'] = $this->hash_id;
        }
        
        // Replace foreign key IDs with HashIds
        foreach (['user_id', 'category_id', 'department_id'] as $field) {
            if (isset($array[$field]) && $array[$field]) {
                $array[$field] = hashid_encode($array[$field]);
            }
        }
        
        return $array;
    }
}
```

## 🌐 API Usage Examples

### RESTful API with HashIds

```php
// app/Http/Controllers/Api/UserController.php
class UserController extends Controller
{
    protected UserRepository $repository;
    
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * GET /api/users/gY6N8
     */
    public function show($id)
    {
        // $id is HashId - automatically decoded by repository
        $user = $this->repository->find($id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        return response()->json($user); // Response contains HashIds
    }
    
    /**
     * PUT /api/users/gY6N8
     */
    public function update(Request $request, $id)
    {
        // $id is HashId - automatically decoded
        $user = $this->repository->update($request->validated(), $id);
        
        return response()->json($user);
    }
    
    /**
     * DELETE /api/users/gY6N8
     */
    public function destroy($id)
    {
        // $id is HashId - automatically decoded
        $deleted = $this->repository->delete($id);
        
        return response()->json(['success' => $deleted]);
    }
    
    /**
     * GET /api/users?filter=department_id:m3K9x
     */
    public function index()
    {
        // RequestCriteria automatically handles HashIds in filters
        $users = $this->repository->paginate(15);
        
        return response()->json($users);
    }
}
```

### Advanced API Endpoints

```php
class PostController extends Controller
{
    /**
     * GET /api/users/gY6N8/posts
     */
    public function userPosts($userId)
    {
        // $userId is HashId - automatically decoded
        $posts = $this->postRepository->findWhere([
            'user_id' => $userId,
            'status' => 'published',
        ]);
        
        return response()->json($posts);
    }
    
    /**
     * POST /api/posts
     */
    public function store(Request $request)
    {
        $data = $request->validated();
        
        // HashId fields in request are automatically decoded
        // user_id: "gY6N8" → decoded to actual user ID
        // category_id: "m3K9x" → decoded to actual category ID
        
        $post = $this->repository->create($data);
        
        return response()->json($post, 201);
    }
    
    /**
     * GET /api/posts/search?user_ids=gY6N8,k2V9m&category_id=m3K9x
     */
    public function search(Request $request)
    {
        $criteria = [];
        
        // Multiple user HashIds
        if ($userIds = $request->get('user_ids')) {
            $criteria['user_ids'] = explode(',', $userIds); // Auto-decoded by repository
        }
        
        // Category HashId
        if ($categoryId = $request->get('category_id')) {
            $criteria['category_id'] = $categoryId; // Auto-decoded by repository
        }
        
        $posts = $this->repository->findWhere($criteria);
        
        return response()->json($posts);
    }
}
```

### Route Model Binding with HashIds

```php
// routes/api.php
Route::apiResource('users', UserController::class);
Route::apiResource('posts', PostController::class);

// app/Http/Controllers/Api/UserController.php
class UserController extends Controller
{
    /**
     * Route model binding automatically uses HashId
     * GET /api/users/gY6N8
     */
    public function show(User $user)
    {
        // $user is automatically resolved using HashId
        return response()->json($user);
    }
    
    /**
     * PUT /api/users/gY6N8
     */
    public function update(Request $request, User $user)
    {
        // $user is automatically resolved using HashId
        $user->update($request->validated());
        
        return response()->json($user);
    }
}

// app/Models/User.php - Enable HashId route binding
class User extends Model
{
    public function getRouteKeyName()
    {
        return 'id'; // Still use ID column
    }
    
    public function resolveRouteBinding($value, $field = null)
    {
        // Decode HashId to find user
        $id = hashid_decode($value, 'users');
        return $id ? $this->find($id) : null;
    }
}
```

## 🔧 Custom HashId Strategies

### Multi-Tenant HashIds

```php
class TenantAwareRepository extends BaseRepository
{
    /**
     * Use tenant-specific HashId salt
     */
    protected function getHashIdConnection(): string
    {
        $tenant = auth()->user()->tenant ?? 'default';
        return "tenant_{$tenant}";
    }
    
    protected function processIdValue($value)
    {
        if (is_string($value) && $this->looksLikeHashId($value)) {
            return hashid_decode($value, $this->getHashIdConnection());
        }
        
        return $value;
    }
    
    /**
     * Encode ID with tenant-specific connection
     */
    public function encodeId($id): string
    {
        return hashid_encode($id, $this->getHashIdConnection());
    }
}

// Configure tenant-specific connections
// config/hashids.php
'connections' => [
    'tenant_company_a' => [
        'salt' => env('APP_KEY') . '_company_a',
        'length' => 8,
    ],
    'tenant_company_b' => [
        'salt' => env('APP_KEY') . '_company_b', 
        'length' => 8,
    ],
],
```

### Time-Based HashIds

```php
class TimeBasedHashIdRepository extends BaseRepository
{
    /**
     * Include timestamp in HashId for additional security
     */
    public function createTimeBasedHashId($id): string
    {
        $timestamp = now()->timestamp;
        $combined = ($id * 1000000) + ($timestamp % 1000000);
        
        return hashid_encode($combined, 'time_based');
    }
    
    public function decodeTimeBasedHashId($hashId): ?array
    {
        $combined = hashid_decode($hashId, 'time_based');
        
        if (!$combined) {
            return null;
        }
        
        $id = intval($combined / 1000000);
        $timestamp = $combined % 1000000;
        
        return [
            'id' => $id,
            'timestamp' => $timestamp,
            'created_at' => Carbon::createFromTimestamp($timestamp),
        ];
    }
}
```

### Resource-Specific HashIds

```php
class ResourceSpecificHashIds
{
    protected static $connections = [
        'users' => 'users',
        'posts' => 'posts', 
        'comments' => 'posts', // Share with posts
        'orders' => 'sensitive',
        'payments' => 'sensitive',
    ];
    
    public static function encode($id, $resource): string
    {
        $connection = static::$connections[$resource] ?? 'default';
        return hashid_encode($id, $connection);
    }
    
    public static function decode($hashId, $resource): ?int
    {
        $connection = static::$connections[$resource] ?? 'default';
        return hashid_decode($hashId, $connection);
    }
}

// Usage in transformers
class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id' => ResourceSpecificHashIds::encode($user->id, 'users'),
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
```

## ⚡ Performance Optimization

### HashId Caching

```php
class CachedHashIdRepository extends BaseRepository
{
    protected $hashIdCache = [];
    
    /**
     * Cache HashId encoding/decoding
     */
    protected function processIdValue($value)
    {
        if (!is_string($value) || !$this->looksLikeHashId($value)) {
            return $value;
        }
        
        // Check cache first
        if (isset($this->hashIdCache[$value])) {
            return $this->hashIdCache[$value];
        }
        
        // Decode and cache
        $decoded = hashid_decode($value);
        $this->hashIdCache[$value] = $decoded;
        
        return $decoded;
    }
    
    /**
     * Batch decode HashIds for better performance
     */
    public function batchDecodeHashIds(array $hashIds): array
    {
        $decoded = [];
        $toProcess = [];
        
        // Check cache first
        foreach ($hashIds as $hashId) {
            if (isset($this->hashIdCache[$hashId])) {
                $decoded[$hashId] = $this->hashIdCache[$hashId];
            } else {
                $toProcess[] = $hashId;
            }
        }
        
        // Process uncached HashIds
        foreach ($toProcess as $hashId) {
            $decodedId = hashid_decode($hashId);
            $this->hashIdCache[$hashId] = $decodedId;
            $decoded[$hashId] = $decodedId;
        }
        
        return $decoded;
    }
}
```

### Lazy Loading HashId Processing

```php
class LazyHashIdRepository extends BaseRepository
{
    /**
     * Only process HashIds when needed
     */
    protected function processIdValue($value)
    {
        // Skip processing for obvious numeric IDs
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        // Only process strings that look like HashIds
        if (is_string($value) && $this->looksLikeHashId($value)) {
            return $this->decodeHashId($value);
        }
        
        return $value;
    }
    
    protected function looksLikeHashId(string $value): bool
    {
        // Quick checks to avoid unnecessary processing
        $length = strlen($value);
        
        // Must be within expected HashId length range
        if ($length < 4 || $length > 20) {
            return false;
        }
        
        // Must contain only valid HashId characters
        if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            return false;
        }
        
        // Must not be all numbers (likely a string ID)
        if (ctype_digit($value)) {
            return false;
        }
        
        return true;
    }
}
```

### Optimized HashId Validation

```php
class ValidatingHashIdRepository extends BaseRepository
{
    /**
     * Validate HashId before processing
     */
    protected function processIdValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        
        // Quick validation before expensive decode operation
        if (!$this->isValidHashId($value)) {
            return $value; // Return as-is if not a valid HashId
        }
        
        $decoded = hashid_decode($value);
        
        // Additional validation after decode
        if ($decoded === null || $decoded <= 0) {
            throw new InvalidArgumentException("Invalid HashId: {$value}");
        }
        
        return $decoded;
    }
    
    protected function isValidHashId(string $value): bool
    {
        // Implement validation logic
        $minLength = config('hashids.default.length', 6);
        
        return strlen($value) >= $minLength && 
               preg_match('/^[a-zA-Z0-9]+$/', $value) &&
               !ctype_digit($value);
    }
}
```

## 🔒 Security Considerations

### HashId Security Best Practices

```php
class SecureHashIdRepository extends BaseRepository
{
    /**
     * Validate HashId belongs to current user/tenant
     */
    public function findSecure($id, $userId = null)
    {
        $decoded = $this->processIdValue($id);
        
        // Additional security check
        if ($userId && !$this->belongsToUser($decoded, $userId)) {
            throw new UnauthorizedException('Resource not accessible');
        }
        
        return $this->find($decoded);
    }
    
    protected function belongsToUser($resourceId, $userId): bool
    {
        // Implement ownership validation
        return $this->model->where('id', $resourceId)
                          ->where('user_id', $userId)
                          ->exists();
    }
    
    /**
     * Rate limit HashId decoding to prevent brute force
     */
    protected function processIdValue($value)
    {
        if (is_string($value) && $this->looksLikeHashId($value)) {
            // Rate limit per IP
            $key = 'hashid_decode_' . request()->ip();
            
            if (Cache::get($key, 0) > 100) { // 100 attempts per minute
                throw new TooManyRequestsException('Rate limit exceeded');
            }
            
            Cache::increment($key);
            Cache::expire($key, 60); // Reset after 1 minute
            
            return hashid_decode($value);
        }
        
        return $value;
    }
}
```

### Audit Trail for HashId Operations

```php
class AuditableHashIdRepository extends BaseRepository
{
    /**
     * Log HashId operations for security auditing
     */
    protected function processIdValue($value)
    {
        if (is_string($value) && $this->looksLikeHashId($value)) {
            $decoded = hashid_decode($value);
            
            // Log the operation
            $this->logHashIdOperation($value, $decoded);
            
            return $decoded;
        }
        
        return $value;
    }
    
    protected function logHashIdOperation($hashId, $decodedId)
    {
        Log::info('HashId Operation', [
            'hash_id' => $hashId,
            'decoded_id' => $decodedId,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'repository' => static::class,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

---

**Next:** Learn about **[Events System](events.md)** for repository lifecycle management and automation.