# Advanced Examples

Real-world examples and use cases for the Apiato Repository package.

## Complete User Management System

### User Repository with All Features

```php
<?php

namespace App\Containers\User\Data\Repositories;

use App\Containers\User\Models\User;
use App\Ship\Parents\Repositories\Repository;
use Apiato\Repository\Contracts\CacheableInterface;
use Apiato\Repository\Traits\CacheableRepository;

class UserRepository extends Repository implements CacheableInterface
{
    use CacheableRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'username' => 'like',
        'status' => 'in',
        'role_id' => 'in',
        'created_at' => 'between',
        'last_login_at' => 'between',
    ];

    protected int $cacheMinutes = 60;
    protected array $cacheTags = ['users', 'user_profiles'];

    public function model(): string
    {
        return User::class;
    }

    public function presenter(): string
    {
        return UserPresenter::class;
    }

    /**
     * Find active users with recent activity
     */
    public function findActiveUsersWithRecentActivity(int $days = 30): Collection
    {
        return $this->cacheMinutes(120)
            ->cacheKey('active_users_recent_' . $days)
            ->query()
            ->where('status', 'active')
            ->where('last_login_at', '>=', now()->subDays($days))
            ->with(['profile:id,user_id,avatar,bio'])
            ->orderBy('last_login_at', 'desc')
            ->get();
    }

    /**
     * Search users with advanced filters
     */
    public function searchUsersAdvanced(array $filters): LengthAwarePaginator
    {
        $query = $this->query();

        // Name or email search
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        // Role filter
        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->whereIn('name', (array) $filters['role']);
            });
        }

        // Date range filter
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Has profile filter
        if (isset($filters['has_profile']) && $filters['has_profile']) {
            $query->whereHas('profile');
        }

        // Verification status
        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        return $query
            ->with(['profile:id,user_id,avatar', 'roles:id,name'])
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        return $this->cacheMinutes(1440) // Cache for 24 hours
            ->cacheKey('user_statistics')
            ->executeCallback(function () {
                return [
                    'total_users' => $this->query()->count(),
                    'active_users' => $this->query()->where('status', 'active')->count(),
                    'verified_users' => $this->query()->whereNotNull('email_verified_at')->count(),
                    'users_with_profiles' => $this->query()->whereHas('profile')->count(),
                    'recent_registrations' => $this->query()
                        ->where('created_at', '>=', now()->subDays(30))
                        ->count(),
                    'top_roles' => $this->query()
                        ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                        ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                        ->groupBy('roles.id', 'roles.name')
                        ->selectRaw('roles.name, COUNT(*) as count')
                        ->orderBy('count', 'desc')
                        ->limit(5)
                        ->get()
                        ->toArray(),
                ];
            });
    }

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(array $userIds, string $status): int
    {
        // Decode HashIds if needed
        $decodedIds = array_map([$this, 'processIdValue'], $userIds);
        
        $updated = $this->query()
            ->whereIn('id', $decodedIds)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

        // Clear cache for affected users
        $this->clearCacheForUsers($decodedIds);

        return $updated;
    }

    /**
     * Get users by location
     */
    public function getUsersByLocation(string $country, ?string $city = null): Collection
    {
        $cacheKey = 'users_location_' . $country . ($city ? "_{$city}" : '');
        
        return $this->cacheMinutes(180)
            ->cacheKey($cacheKey)
            ->query()
            ->whereHas('profile', function ($query) use ($country, $city) {
                $query->where('country', $country);
                if ($city) {
                    $query->where('city', $city);
                }
            })
            ->with(['profile:id,user_id,country,city,avatar'])
            ->get();
    }

    /**
     * Clear cache for specific users
     */
    protected function clearCacheForUsers(array $userIds): void
    {
        foreach ($userIds as $userId) {
            Cache::tags(["user_{$userId}"])->flush();
        }
        
        // Clear general user caches
        Cache::tags(['users', 'user_statistics'])->flush();
    }
}
```

### Advanced User Criteria

```php
<?php

namespace App\Containers\User\Data\Criterias;

use App\Ship\Parents\Criterias\Criteria;
use Apiato\Repository\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class AdvancedUserSearchCriteria extends Criteria
{
    public function __construct(
        protected string $searchTerm,
        protected array $searchFields = ['name', 'email', 'username'],
        protected bool $includeProfiles = false
    ) {
        parent::__construct();
    }

    protected function applyEnhanced(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->where(function ($query) {
            // Search in user fields
            foreach ($this->searchFields as $field) {
                $query->orWhere($field, 'like', "%{$this->searchTerm}%");
            }

            // Search in profile fields if requested
            if ($this->includeProfiles) {
                $query->orWhereHas('profile', function ($profileQuery) {
                    $profileQuery->where('bio', 'like', "%{$this->searchTerm}%")
                               ->orWhere('company', 'like', "%{$this->searchTerm}%")
                               ->orWhere('job_title', 'like', "%{$this->searchTerm}%");
                });
            }

            // Search by HashId if the term looks like one
            if ($this->looksLikeHashId($this->searchTerm)) {
                $decodedId = $this->decodeHashId($this->searchTerm);
                if ($decodedId) {
                    $query->orWhere('id', $decodedId);
                }
            }
        });
    }
}

class UsersByRoleAndStatusCriteria extends Criteria
{
    public function __construct(
        protected array $roles,
        protected array $statuses = ['active']
    ) {
        parent::__construct();
    }

    protected function applyEnhanced(Builder $model, RepositoryInterface $repository): Builder
    {
        return $model->whereIn('status', $this->statuses)
                    ->whereHas('roles', function ($query) {
                        $query->whereIn('name', $this->roles);
                    });
    }
}

class RecentlyActiveUsersCriteria extends Criteria
{
    public function __construct(
        protected int $days = 30,
        protected bool $includeSessions = false
    ) {
        parent::__construct();
    }

    protected function applyEnhanced(Builder $model, RepositoryInterface $repository): Builder
    {
        $query = $model->where('last_login_at', '>=', now()->subDays($this->days));

        if ($this->includeSessions) {
            $query->orWhereHas('sessions', function ($sessionQuery) {
                $sessionQuery->where('last_activity', '>=', now()->subDays($this->days)->timestamp);
            });
        }

        return $query->orderBy('last_login_at', 'desc');
    }
}
```

### User Transformer with Full Features

```php
<?php

namespace App\Containers\User\UI\API\Transformers;

use App\Containers\User\Models\User;
use App\Ship\Parents\Transformers\Transformer;

class UserTransformer extends Transformer
{
    protected array $availableIncludes = [
        'profile',
        'roles',
        'permissions',
        'posts',
        'posts_count',
        'comments_count',
        'followers_count',
        'following_count',
        'last_login',
        'account_status',
    ];

    protected array $defaultIncludes = [
        'account_status'
    ];

    protected function transformData($user): array
    {
        return [
            'id' => $user->id, // Auto-encoded to HashId
            'name' => $user->name,
            'email' => $this->hideEmailIfPrivate($user),
            'username' => $user->username,
            'status' => $user->status,
            'verified' => !is_null($user->email_verified_at),
            'member_since' => $this->formatDate($user->created_at),
            'last_updated' => $this->formatDate($user->updated_at),
            'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            'profile_url' => $this->resourceUrl('users', $user->id),
        ];
    }

    public function includeProfile(User $user)
    {
        if (!$user->profile) {
            return $this->null();
        }

        return $this->item($user->profile, new ProfileTransformer());
    }

    public function includeRoles(User $user)
    {
        return $this->collection($user->roles, new RoleTransformer());
    }

    public function includePermissions(User $user)
    {
        $permissions = $user->getAllPermissions();
        return $this->collection($permissions, new PermissionTransformer());
    }

    public function includePosts(User $user)
    {
        $posts = $user->posts()
                     ->where('status', 'published')
                     ->latest()
                     ->limit(10)
                     ->get();
                     
        return $this->collection($posts, new PostTransformer());
    }

    public function includePostsCount(User $user)
    {
        return $this->primitive(
            $user->posts()->where('status', 'published')->count()
        );
    }

    public function includeCommentsCount(User $user)
    {
        return $this->primitive($user->comments()->count());
    }

    public function includeFollowersCount(User $user)
    {
        return $this->primitive($user->followers()->count());
    }

    public function includeFollowingCount(User $user)
    {
        return $this->primitive($user->following()->count());
    }

    public function includeLastLogin(User $user)
    {
        return $this->primitive([
            'timestamp' => $this->formatDate($user->last_login_at),
            'ip_address' => $this->hideIpIfPrivate($user),
            'user_agent' => $user->last_login_user_agent,
            'relative' => $user->last_login_at?->diffForHumans(),
        ]);
    }

    public function includeAccountStatus(User $user)
    {
        return $this->primitive([
            'is_active' => $user->status === 'active',
            'is_verified' => !is_null($user->email_verified_at),
            'is_online' => $user->last_seen_at?->gt(now()->subMinutes(5)) ?? false,
            'can_login' => in_array($user->status, ['active', 'pending']),
            'requires_password_reset' => $user->password_reset_required ?? false,
            'two_factor_enabled' => $user->two_factor_secret !== null,
        ]);
    }

    protected function hideEmailIfPrivate(User $user): ?string
    {
        // Show email to the user themselves, admins, or if profile is public
        if (auth()->id() === $user->id || 
            auth()->user()?->hasRole('admin') || 
            $user->profile?->email_public) {
            return $user->email;
        }

        // Return masked email for privacy
        return $this->maskEmail($user->email);
    }

    protected function hideIpIfPrivate(User $user): ?string
    {
        // Only show IP to the user themselves or admins
        if (auth()->id() === $user->id || auth()->user()?->hasRole('admin')) {
            return $user->last_login_ip;
        }

        return null;
    }

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];

        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        
        return $maskedName . '@' . $domain;
    }
}
```

## E-commerce Product System

### Product Repository with Complex Queries

```php
<?php

namespace App\Containers\Product\Data\Repositories;

use App\Containers\Product\Models\Product;
use App\Ship\Parents\Repositories\Repository;

class ProductRepository extends Repository
{
    protected array $fieldSearchable = [
        'name' => 'like',
        'sku' => '=',
        'category_id' => 'in',
        'brand_id' => 'in',
        'status' => 'in',
        'price' => 'between',
        'stock_quantity' => 'between',
        'is_featured' => '=',
        'created_at' => 'between',
    ];

    protected int $cacheMinutes = 120;
    protected array $cacheTags = ['products', 'catalog'];

    public function model(): string
    {
        return Product::class;
    }

    /**
     * Advanced product search with filters, sorting, and aggregations
     */
    public function searchProducts(array $filters): array
    {
        $query = $this->query();

        // Text search across multiple fields
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('tags', function ($tagQuery) use ($search) {
                      $tagQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Category filter with subcategories
        if (isset($filters['category'])) {
            $categoryIds = $this->getCategoryWithChildren($filters['category']);
            $query->whereIn('category_id', $categoryIds);
        }

        // Brand filter
        if (isset($filters['brands'])) {
            $query->whereIn('brand_id', (array) $filters['brands']);
        }

        // Price range
        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        // Rating filter
        if (isset($filters['min_rating'])) {
            $query->whereHas('reviews', function ($reviewQuery) use ($filters) {
                $reviewQuery->selectRaw('AVG(rating) as avg_rating')
                           ->groupBy('product_id')
                           ->havingRaw('AVG(rating) >= ?', [$filters['min_rating']]);
            });
        }

        // Availability filter
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->where('stock_quantity', '>', 0);
        }

        // Featured products
        if (isset($filters['featured']) && $filters['featured']) {
            $query->where('is_featured', true);
        }

        // Discount filter
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $query->where('sale_price', '<', 'price')
                  ->whereNotNull('sale_price');
        }

        // Apply sorting
        $this->applySorting($query, $filters);

        // Get paginated results
        $products = $query
            ->with(['category:id,name,slug', 'brand:id,name', 'images:id,product_id,url'])
            ->paginate($filters['per_page'] ?? 24);

        // Get aggregations for filters
        $aggregations = $this->getProductAggregations($filters);

        return [
            'products' => $products,
            'aggregations' => $aggregations,
            'filters_applied' => $filters,
        ];
    }

    /**
     * Get related products using multiple algorithms
     */
    public function getRelatedProducts(Product $product, int $limit = 8): Collection
    {
        $cacheKey = "related_products_{$product->id}_{$limit}";
        
        return $this->cacheMinutes(360)
            ->cacheKey($cacheKey)
            ->executeCallback(function () use ($product, $limit) {
                // Multiple strategies for finding related products
                $related = collect();

                // 1. Same category
                $sameCategory = $this->query()
                    ->where('category_id', $product->category_id)
                    ->where('id', '!=', $product->id)
                    ->where('status', 'active')
                    ->inRandomOrder()
                    ->limit($limit / 2)
                    ->get();

                $related = $related->merge($sameCategory);

                // 2. Same brand
                if ($related->count() < $limit && $product->brand_id) {
                    $sameBrand = $this->query()
                        ->where('brand_id', $product->brand_id)
                        ->where('id', '!=', $product->id)
                        ->whereNotIn('id', $related->pluck('id'))
                        ->where('status', 'active')
                        ->inRandomOrder()
                        ->limit($limit - $related->count())
                        ->get();

                    $related = $related->merge($sameBrand);
                }

                // 3. Similar price range
                if ($related->count() < $limit) {
                    $priceMin = $product->price * 0.8;
                    $priceMax = $product->price * 1.2;

                    $similarPrice = $this->query()
                        ->whereBetween('price', [$priceMin, $priceMax])
                        ->where('id', '!=', $product->id)
                        ->whereNotIn('id', $related->pluck('id'))
                        ->where('status', 'active')
                        ->inRandomOrder()
                        ->limit($limit - $related->count())
                        ->get();

                    $related = $related->merge($similarPrice);
                }

                // 4. Fill remaining with popular products
                if ($related->count() < $limit) {
                    $popular = $this->query()
                        ->where('id', '!=', $product->id)
                        ->whereNotIn('id', $related->pluck('id'))
                        ->where('status', 'active')
                        ->orderBy('views_count', 'desc')
                        ->limit($limit - $related->count())
                        ->get();

                    $related = $related->merge($popular);
                }

                return $related->take($limit)->values();
            });
    }

    /**
     * Get product analytics and insights
     */
    public function getProductAnalytics(Product $product): array
    {
        return $this->cacheMinutes(60)
            ->cacheKey("product_analytics_{$product->id}")
            ->executeCallback(function () use ($product) {
                return [
                    'views' => [
                        'total' => $product->views_count,
                        'today' => $this->getViewsCount($product, 'today'),
                        'this_week' => $this->getViewsCount($product, 'week'),
                        'this_month' => $this->getViewsCount($product, 'month'),
                    ],
                    'sales' => [
                        'total_quantity' => $product->orders()->sum('quantity'),
                        'total_revenue' => $product->orders()->sum('total'),
                        'last_30_days' => $this->getSalesData($product, 30),
                    ],
                    'inventory' => [
                        'current_stock' => $product->stock_quantity,
                        'reserved_stock' => $product->getReservedStock(),
                        'available_stock' => $product->getAvailableStock(),
                        'low_stock_threshold' => $product->low_stock_threshold,
                        'is_low_stock' => $product->isLowStock(),
                    ],
                    'reviews' => [
                        'average_rating' => $product->reviews()->avg('rating'),
                        'total_reviews' => $product->reviews()->count(),
                        'rating_distribution' => $this->getRatingDistribution($product),
                    ],
                    'performance' => [
                        'conversion_rate' => $this->getConversionRate($product),
                        'cart_abandonment_rate' => $this->getCartAbandonmentRate($product),
                        'return_rate' => $this->getReturnRate($product),
                    ],
                ];
            });
    }

    /**
     * Bulk update product prices
     */
    public function bulkUpdatePrices(array $updates): array
    {
        $results = ['updated' => 0, 'errors' => []];

        DB::transaction(function () use ($updates, &$results) {
            foreach ($updates as $update) {
                try {
                    $productId = $this->processIdValue($update['id']);
                    
                    $product = $this->findOrFail($productId);
                    
                    $product->update([
                        'price' => $update['price'],
                        'sale_price' => $update['sale_price'] ?? null,
                        'updated_at' => now(),
                    ]);

                    $results['updated']++;

                    // Log price change
                    $this->logPriceChange($product, $update);

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'id' => $update['id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        // Clear product caches
        Cache::tags(['products', 'catalog'])->flush();

        return $results;
    }

    protected function applySorting($query, array $filters): void
    {
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'popularity':
                $query->orderBy('views_count', 'desc');
                break;
            case 'rating':
                $query->leftJoin('reviews', 'products.id', '=', 'reviews.product_id')
                      ->selectRaw('products.*, AVG(reviews.rating) as avg_rating')
                      ->groupBy('products.id')
                      ->orderBy('avg_rating', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
        }
    }

    protected function getProductAggregations(array $filters): array
    {
        $baseQuery = $this->query()->where('status', 'active');

        return [
            'price_range' => [
                'min' => $baseQuery->min('price'),
                'max' => $baseQuery->max('price'),
            ],
            'categories' => $this->getCategoryCounts($baseQuery),
            'brands' => $this->getBrandCounts($baseQuery),
            'average_rating' => $baseQuery->join('reviews', 'products.id', '=', 'reviews.product_id')
                                        ->avg('reviews.rating'),
            'total_products' => $baseQuery->count(),
        ];
    }
}
```

## Real-time Chat System

### Message Repository with Real-time Features

```php
<?php

namespace App\Containers\Chat\Data\Repositories;

use App\Containers\Chat\Models\Message;
use App\Ship\Parents\Repositories\Repository;

class MessageRepository extends Repository
{
    protected array $fieldSearchable = [
        'content' => 'like',
        'user_id' => '=',
        'conversation_id' => '=',
        'message_type' => 'in',
        'created_at' => 'between',
    ];

    protected int $cacheMinutes = 30; // Short cache for real-time data
    protected array $cacheTags = ['messages', 'chat'];

    public function model(): string
    {
        return Message::class;
    }

    /**
     * Get messages for a conversation with pagination
     */
    public function getConversationMessages(
        string $conversationId, 
        int $limit = 50, 
        ?string $before = null
    ): array {
        $conversationId = $this->processIdValue($conversationId);
        
        $query = $this->query()
            ->where('conversation_id', $conversationId)
            ->with(['user:id,name,avatar', 'attachments', 'reactions.user:id,name'])
            ->orderBy('created_at', 'desc');

        // Cursor pagination for real-time performance
        if ($before) {
            $beforeMessage = $this->findByHashId($before);
            if ($beforeMessage) {
                $query->where('created_at', '<', $beforeMessage->created_at);
            }
        }

        $messages = $query->limit($limit + 1)->get();

        $hasMore = $messages->count() > $limit;
        if ($hasMore) {
            $messages->pop();
        }

        return [
            'messages' => $messages->reverse()->values(),
            'has_more' => $hasMore,
            'next_cursor' => $hasMore ? $this->encodeHashId($messages->first()->id) : null,
        ];
    }

    /**
     * Send a new message
     */
    public function sendMessage(array $data): Message
    {
        $message = DB::transaction(function () use ($data) {
            // Create the message
            $message = $this->create([
                'conversation_id' => $this->processIdValue($data['conversation_id']),
                'user_id' => $data['user_id'],
                'content' => $data['content'],
                'message_type' => $data['type'] ?? 'text',
                'reply_to_id' => isset($data['reply_to']) ? $this->processIdValue($data['reply_to']) : null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Handle attachments
            if (isset($data['attachments'])) {
                $this->attachFiles($message, $data['attachments']);
            }

            // Update conversation last message
            $this->updateConversationLastMessage($message);

            // Mark conversation as unread for other participants
            $this->markConversationUnread($message->conversation_id, $data['user_id']);

            return $message->load(['user:id,name,avatar', 'attachments']);
        });

        // Clear relevant caches
        $this->clearConversationCaches($message->conversation_id);

        // Broadcast real-time event
        $this->broadcastNewMessage($message);

        return $message;
    }

    /**
     * Search messages across conversations
     */
    public function searchMessages(
        array $conversationIds, 
        string $query, 
        array $filters = []
    ): LengthAwarePaginator {
        $search = $this->query()
            ->whereIn('conversation_id', array_map([$this, 'processIdValue'], $conversationIds))
            ->where('content', 'like', "%{$query}%");

        // Filter by message type
        if (isset($filters['type'])) {
            $search->where('message_type', $filters['type']);
        }

        // Filter by user
        if (isset($filters['user_id'])) {
            $search->where('user_id', $this->processIdValue($filters['user_id']));
        }

        // Filter by date range
        if (isset($filters['date_from'])) {
            $search->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $search->where('created_at', '<=', $filters['date_to']);
        }

        // Filter by has attachments
        if (isset($filters['has_attachments']) && $filters['has_attachments']) {
            $search->whereHas('attachments');
        }

        return $search
            ->with(['user:id,name,avatar', 'conversation:id,name', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get message analytics for a conversation
     */
    public function getConversationAnalytics(string $conversationId, int $days = 30): array
    {
        $conversationId = $this->processIdValue($conversationId);
        $startDate = now()->subDays($days);

        return $this->cacheMinutes(60)
            ->cacheKey("conversation_analytics_{$conversationId}_{$days}")
            ->executeCallback(function () use ($conversationId, $startDate) {
                $query = $this->query()
                    ->where('conversation_id', $conversationId)
                    ->where('created_at', '>=', $startDate);

                return [
                    'total_messages' => $query->count(),
                    'messages_by_user' => $query->groupBy('user_id')
                        ->selectRaw('user_id, COUNT(*) as count')
                        ->with('user:id,name')
                        ->get()
                        ->toArray(),
                    'messages_by_type' => $query->groupBy('message_type')
                        ->selectRaw('message_type, COUNT(*) as count')
                        ->get()
                        ->toArray(),
                    'daily_activity' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->toArray(),
                    'peak_hours' => $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                        ->groupBy('hour')
                        ->orderBy('count', 'desc')
                        ->get()
                        ->toArray(),
                    'attachment_stats' => [
                        'total_attachments' => $query->whereHas('attachments')->count(),
                        'by_type' => $query->join('message_attachments', 'messages.id', '=', 'message_attachments.message_id')
                            ->groupBy('message_attachments.file_type')
                            ->selectRaw('message_attachments.file_type, COUNT(*) as count')
                            ->get()
                            ->toArray(),
                    ],
                ];
            });
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(array $messageIds, int $userId): int
    {
        $decodedIds = array_map([$this, 'processIdValue'], $messageIds);
        
        return DB::table('message_reads')
            ->insertOrIgnore(
                collect($decodedIds)->map(function ($messageId) use ($userId) {
                    return [
                        'message_id' => $messageId,
                        'user_id' => $userId,
                        'read_at' => now(),
                    ];
                })->toArray()
            );
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount(int $userId, ?string $conversationId = null): int
    {
        $query = $this->query()
            ->where('user_id', '!=', $userId)
            ->whereNotExists(function ($subQuery) use ($userId) {
                $subQuery->select(DB::raw(1))
                         ->from('message_reads')
                         ->whereColumn('message_reads.message_id', 'messages.id')
                         ->where('message_reads.user_id', $userId);
            });

        if ($conversationId) {
            $query->where('conversation_id', $this->processIdValue($conversationId));
        }

        return $query->count();
    }

    protected function broadcastNewMessage(Message $message): void
    {
        broadcast(new NewMessageEvent($message))
            ->toOthers();
    }

    protected function clearConversationCaches(int $conversationId): void
    {
        Cache::tags([
            "conversation_{$conversationId}",
            'messages',
            'chat'
        ])->flush();
    }
}
```

## Multi-tenant Blog System

### Post Repository with Tenant Isolation

```php
<?php

namespace App\Containers\Blog\Data\Repositories;

use App\Containers\Blog\Models\Post;
use App\Ship\Parents\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;

class PostRepository extends Repository
{
    protected array $fieldSearchable = [
        'title' => 'like',
        'content' => 'like',
        'slug' => '=',
        'status' => 'in',
        'category_id' => 'in',
        'author_id' => '=',
        'published_at' => 'between',
        'featured' => '=',
    ];

    protected int $cacheMinutes = 180;
    protected array $cacheTags = ['posts', 'blog'];

    public function model(): string
    {
        return Post::class;
    }

    /**
     * Apply global tenant scope
     */
    protected function applyGlobalScope(Builder $query): Builder
    {
        if ($tenantId = $this->getCurrentTenantId()) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query;
    }

    /**
     * Get published posts with advanced filtering
     */
    public function getPublishedPosts(array $filters = []): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        // Category filter
        if (isset($filters['category'])) {
            if (is_array($filters['category'])) {
                $categoryIds = array_map([$this, 'processIdValue'], $filters['category']);
                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->where('category_id', $this->processIdValue($filters['category']));
            }
        }

        // Author filter
        if (isset($filters['author'])) {
            $query->where('author_id', $this->processIdValue($filters['author']));
        }

        // Tag filter
        if (isset($filters['tags'])) {
            $tagIds = array_map([$this, 'processIdValue'], (array) $filters['tags']);
            $query->whereHas('tags', function ($tagQuery) use ($tagIds) {
                $tagQuery->whereIn('tags.id', $tagIds);
            });
        }

        // Date range filter
        if (isset($filters['date_from'])) {
            $query->where('published_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('published_at', '<=', $filters['date_to']);
        }

        // Featured filter
        if (isset($filters['featured']) && $filters['featured']) {
            $query->where('featured', true);
        }

        // Text search
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                           ->orWhere('excerpt', 'like', "%{$search}%")
                           ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort'] ?? 'published_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        
        if ($sortBy === 'popular') {
            $query->orderBy('views_count', 'desc')
                  ->orderBy('published_at', 'desc');
        } elseif ($sortBy === 'trending') {
            $query->where('published_at', '>=', now()->subDays(7))
                  ->orderBy('views_count', 'desc');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query
            ->with([
                'author:id,name,avatar',
                'category:id,name,slug,color',
                'tags:id,name,slug,color',
                'featuredImage:id,post_id,url,alt_text'
            ])
            ->paginate($filters['per_page'] ?? 12);
    }

    /**
     * Get related posts using content similarity
     */
    public function getRelatedPosts(Post $post, int $limit = 6): Collection
    {
        return $this->cacheMinutes(240)
            ->cacheKey("related_posts_{$post->id}_{$limit}")
            ->executeCallback(function () use ($post, $limit) {
                // Strategy 1: Same category
                $sameCategory = $this->query()
                    ->where('category_id', $post->category_id)
                    ->where('id', '!=', $post->id)
                    ->where('status', 'published')
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get();

                if ($sameCategory->count() >= $limit) {
                    return $sameCategory;
                }

                // Strategy 2: Similar tags
                $similarTags = $this->query()
                    ->where('id', '!=', $post->id)
                    ->where('status', 'published')
                    ->where('published_at', '<=', now())
                    ->whereHas('tags', function ($query) use ($post) {
                        $query->whereIn('tags.id', $post->tags->pluck('id'));
                    })
                    ->whereNotIn('id', $sameCategory->pluck('id'))
                    ->orderBy('published_at', 'desc')
                    ->limit($limit - $sameCategory->count())
                    ->get();

                $related = $sameCategory->merge($similarTags);

                // Strategy 3: Same author
                if ($related->count() < $limit) {
                    $sameAuthor = $this->query()
                        ->where('author_id', $post->author_id)
                        ->where('id', '!=', $post->id)
                        ->where('status', 'published')
                        ->where('published_at', '<=', now())
                        ->whereNotIn('id', $related->pluck('id'))
                        ->orderBy('published_at', 'desc')
                        ->limit($limit - $related->count())
                        ->get();

                    $related = $related->merge($sameAuthor);
                }

                return $related->take($limit);
            });
    }

    /**
     * Get blog statistics and analytics
     */
    public function getBlogStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        return $this->cacheMinutes(60)
            ->cacheKey("blog_statistics_{$dateFrom}_{$dateTo}")
            ->executeCallback(function () use ($dateFrom, $dateTo) {
                $query = $this->query()
                    ->where('status', 'published')
                    ->whereBetween('published_at', [$dateFrom, $dateTo]);

                return [
                    'posts' => [
                        'total_published' => $query->count(),
                        'total_views' => $query->sum('views_count'),
                        'total_comments' => $query->withCount('comments')->sum('comments_count'),
                        'average_views_per_post' => $query->avg('views_count'),
                    ],
                    'top_posts' => $query->orderBy('views_count', 'desc')
                        ->limit(10)
                        ->select(['id', 'title', 'slug', 'views_count', 'published_at'])
                        ->get()
                        ->toArray(),
                    'categories' => $this->getCategoryStatistics($dateFrom, $dateTo),
                    'authors' => $this->getAuthorStatistics($dateFrom, $dateTo),
                    'daily_activity' => $this->getDailyActivity($dateFrom, $dateTo),
                    'engagement' => [
                        'average_comments_per_post' => $this->getAverageCommentsPerPost($dateFrom, $dateTo),
                        'most_commented_posts' => $this->getMostCommentedPosts($dateFrom, $dateTo),
                    ],
                ];
            });
    }

    /**
     * Schedule post publication
     */
    public function schedulePost(array $data): Post
    {
        $post = $this->create(array_merge($data, [
            'status' => 'scheduled',
            'tenant_id' => $this->getCurrentTenantId(),
        ]));

        // Queue publication job
        if ($post->published_at && $post->published_at->isFuture()) {
            PublishScheduledPostJob::dispatch($post)
                ->delay($post->published_at);
        }

        return $post;
    }

    /**
     * Bulk update post status
     */
    public function bulkUpdateStatus(array $postIds, string $status): array
    {
        $decodedIds = array_map([$this, 'processIdValue'], $postIds);
        
        $results = ['updated' => 0, 'errors' => []];

        DB::transaction(function () use ($decodedIds, $status, &$results) {
            foreach ($decodedIds as $postId) {
                try {
                    $post = $this->findOrFail($postId);
                    
                    $post->update([
                        'status' => $status,
                        'published_at' => $status === 'published' ? now() : null,
                    ]);

                    $results['updated']++;

                    // Log status change
                    $this->logStatusChange($post, $status);

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'id' => $this->encodeHashId($postId),
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        // Clear blog caches
        $this->clearBlogCaches();

        return $results;
    }

    protected function getCurrentTenantId(): ?int
    {
        return tenant()?->getKey();
    }

    protected function clearBlogCaches(): void
    {
        Cache::tags(['posts', 'blog', 'blog_statistics'])->flush();
    }
}
```

## Advanced Controller Examples

### RESTful API Controller with All Features

```php
<?php

namespace App\Containers\User\UI\API\Controllers;

use App\Containers\User\Data\Repositories\UserRepository;
use App\Containers\User\UI\API\Requests\CreateUserRequest;
use App\Containers\User\UI\API\Requests\UpdateUserRequest;
use App\Ship\Parents\Controllers\ApiController;
use Apiato\Repository\Criteria\RequestCriteria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends ApiController
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Display a listing of users with advanced filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Apply request criteria for automatic filtering
            $users = $this->userRepository
                ->pushCriteria(new RequestCriteria($request))
                ->cacheMinutes(30)
                ->paginate($request->get('per_page', 15));

            return $this->response([
                'data' => $users,
                'message' => 'Users retrieved successfully',
                'meta' => [
                    'filters_applied' => $request->only(['search', 'filter', 'orderBy']),
                    'cache_enabled' => true,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users', 500, $e);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userRepository->create($request->validated());

            return $this->response([
                'data' => $user,
                'message' => 'User created successfully',
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create user', 422, $e);
        }
    }

    /**
     * Display the specified user
     */
    public function show(string $hashId, Request $request): JsonResponse
    {
        try {
            // Skip presenter if raw data requested
            if ($request->get('raw')) {
                $user = $this->userRepository
                    ->skipPresenter()
                    ->findByHashIdOrFail($hashId);
            } else {
                $user = $this->userRepository
                    ->cacheMinutes(60)
                    ->findByHashIdOrFail($hashId);
            }

            return $this->response([
                'data' => $user,
                'message' => 'User retrieved successfully',
            ]);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user', 500, $e);
        }
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, string $hashId): JsonResponse
    {
        try {
            $user = $this->userRepository->updateByHashId(
                $request->validated(),
                $hashId
            );

            return $this->response([
                'data' => $user,
                'message' => 'User updated successfully',
            ]);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user', 422, $e);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(string $hashId): JsonResponse
    {
        try {
            $this->userRepository->deleteByHashId($hashId);

            return $this->response([
                'message' => 'User deleted successfully',
            ], 204);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user', 500, $e);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->userRepository->getUserStatistics();

            return $this->response([
                'data' => $stats,
                'message' => 'User statistics retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', 500, $e);
        }
    }

    /**
     * Search users with advanced options
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $users = $this->userRepository->searchUsersAdvanced(
                $request->validate([
                    'search' => 'sometimes|string',
                    'status' => 'sometimes|array',
                    'role' => 'sometimes|array',
                    'verified' => 'sometimes|boolean',
                    'has_profile' => 'sometimes|boolean',
                    'date_from' => 'sometimes|date',
                    'date_to' => 'sometimes|date',
                    'sort' => 'sometimes|string|in:name,created_at,last_login_at',
                    'direction' => 'sometimes|string|in:asc,desc',
                    'per_page' => 'sometimes|integer|min:1|max:100',
                ])
            );

            return $this->response([
                'data' => $users,
                'message' => 'Search completed successfully',
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Search failed', 500, $e);
        }
    }

    /**
     * Bulk operations on users
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete,verify',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|string',
        ]);

        try {
            $results = match($request->action) {
                'activate' => $this->userRepository->bulkUpdateStatus($request->user_ids, 'active'),
                'deactivate' => $this->userRepository->bulkUpdateStatus($request->user_ids, 'inactive'),
                'delete' => $this->bulkDelete($request->user_ids),
                'verify' => $this->bulkVerify($request->user_ids),
            };

            return $this->response([
                'data' => $results,
                'message' => "Bulk {$request->action} completed",
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse("Bulk {$request->action} failed", 500, $e);
        }
    }

    protected function bulkDelete(array $userIds): array
    {
        $results = ['deleted' => 0, 'errors' => []];

        foreach ($userIds as $hashId) {
            try {
                $this->userRepository->deleteByHashId($hashId);
                $results['deleted']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'id' => $hashId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function bulkVerify(array $userIds): array
    {
        $results = ['verified' => 0, 'errors' => []];

        foreach ($userIds as $hashId) {
            try {
                $this->userRepository->updateByHashId([
                    'email_verified_at' => now(),
                ], $hashId);
                $results['verified']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'id' => $hashId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function errorResponse(string $message, int $code, ?\Exception $e = null): JsonResponse
    {
        $response = [
            'error' => $message,
            'code' => $code,
        ];

        if (app()->environment('local') && $e) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return response()->json($response, $code);
    }
}
```

## Testing Examples

### Comprehensive Repository Tests

```php
<?php

namespace Tests\Feature\Repositories;

use App\Containers\User\Data\Repositories\UserRepository;
use App\Containers\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    /** @test */
    public function it_can_perform_complete_crud_operations(): void
    {
        // Create
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ];

        $user = $this->repository->create($userData);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);

        // Read
        $foundUser = $this->repository->find($user->id);
        $this->assertEquals($user->id, $foundUser->id);

        // HashId operations
        $hashId = $this->repository->encodeHashId($user->id);
        $foundByHashId = $this->repository->findByHashId($hashId);
        $this->assertEquals($user->id, $foundByHashId->id);

        // Update
        $updateData = ['name' => 'Jane Doe'];
        $updatedUser = $this->repository->update($updateData, $user->id);
        $this->assertEquals($updateData['name'], $updatedUser->name);

        // Delete
        $deleted = $this->repository->delete($user->id);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_can_handle_complex_search_scenarios(): void
    {
        // Create test data
        User::factory()->create(['name' => 'John Doe', 'status' => 'active']);
        User::factory()->create(['name' => 'Jane Smith', 'status' => 'inactive']);
        User::factory()->create(['name' => 'Bob Johnson', 'status' => 'active']);

        // Test advanced search
        $results = $this->repository->searchUsersAdvanced([
            'search' => 'John',
            'status' => ['active'],
        ]);

        $this->assertEquals(2, $results->total());
        $this->assertTrue($results->contains('name', 'John Doe'));
        $this->assertTrue($results->contains('name', 'Bob Johnson'));
    }

    /** @test */
    public function it_caches_expensive_operations(): void
    {
        // Enable caching
        config(['repository.cache.enabled' => true]);

        User::factory()->count(100)->create();

        // First call - should hit database
        $start = microtime(true);
        $firstResult = $this->repository->all();
        $firstTime = microtime(true) - $start;

        // Second call - should hit cache
        $start = microtime(true);
        $secondResult = $this->repository->all();
        $secondTime = microtime(true) - $start;

        $this->assertLessThan($firstTime, $secondTime);
        $this->assertEquals($firstResult->count(), $secondResult->count());
    }

    /** @test */
    public function it_handles_bulk_operations_efficiently(): void
    {
        $users = User::factory()->count(50)->create();
        $userIds = $users->pluck('id')->map(fn($id) => $this->repository->encodeHashId($id))->toArray();

        $results = $this->repository->bulkUpdateStatus($userIds, 'inactive');

        $this->assertEquals(50, $results['updated']);
        $this->assertEmpty($results['errors']);

        // Verify all users are inactive
        $inactiveCount = User::where('status', 'inactive')->count();
        $this->assertEquals(50, $inactiveCount);
    }

    /** @test */
    public function it_maintains_data_integrity_with_transactions(): void
    {
        $initialCount = User::count();

        try {
            DB::transaction(function () {
                $this->repository->create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ]);

                // Simulate an error
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Transaction should be rolled back
        }

        $finalCount = User::count();
        $this->assertEquals($initialCount, $finalCount);
    }
}
```
