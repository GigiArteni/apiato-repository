# Events System - Repository Lifecycle Management

Complete guide to Apiato Repository's event system for automated workflows, audit trails, cache management, and custom business logic integration.

## 📚 Table of Contents

- [Understanding Repository Events](#-understanding-repository-events)
- [Available Events](#-available-events)
- [Event Listeners](#-event-listeners)
- [Automatic Event Triggers](#-automatic-event-triggers)
- [Custom Event Integration](#-custom-event-integration)
- [Real-World Use Cases](#-real-world-use-cases)
- [Performance Considerations](#-performance-considerations)
- [Event-Driven Architecture](#-event-driven-architecture)

## 🎯 Understanding Repository Events

Repository events provide hooks into the repository lifecycle, allowing you to execute custom logic automatically when data changes occur. This enables clean separation of concerns and powerful automation capabilities.

### Event Flow

```php
// When you call repository methods, events are fired automatically:

$user = $repository->create($data);
// 1. RepositoryEntityCreating event fired (before creation)
// 2. Model created in database
// 3. RepositoryEntityCreated event fired (after creation)
// 4. Cache automatically cleared
// 5. Custom listeners executed

$repository->update($data, $id);
// 1. RepositoryEntityUpdating event fired (before update)
// 2. Model updated in database  
// 3. RepositoryEntityUpdated event fired (after update)
// 4. Cache automatically invalidated
// 5. Related data synchronized
```

### Benefits

```php
/**
 * Automatic behaviors enabled by events:
 * 
 * ✅ Cache invalidation
 * ✅ Search index updates
 * ✅ Audit trail logging
 * ✅ Email notifications
 * ✅ Analytics tracking
 * ✅ Data synchronization
 * ✅ Business rule enforcement
 * ✅ Webhook triggers
 */
```

## 📋 Available Events

### Core Repository Events

```php
namespace Apiato\Repository\Events;

/**
 * Creating Events (fired before database operation)
 */
class RepositoryEntityCreating extends RepositoryEventBase
{
    // Fired before create() operation
    // Access: $event->getRepository(), $event->getModel()
    // Can modify data or cancel operation
}

class RepositoryEntityUpdating extends RepositoryEventBase  
{
    // Fired before update() operation
    // Access: $event->getRepository(), $event->getModel()
    // Can modify data or cancel operation
}

class RepositoryEntityDeleting extends RepositoryEventBase
{
    // Fired before delete() operation
    // Access: $event->getRepository(), $event->getModel()
    // Can cancel operation or perform cleanup
}

/**
 * Created Events (fired after database operation)
 */
class RepositoryEntityCreated extends RepositoryEventBase
{
    // Fired after successful create() operation
    // Access: $event->getRepository(), $event->getModel()
    // Cannot modify data, but perfect for side effects
}

class RepositoryEntityUpdated extends RepositoryEventBase
{
    // Fired after successful update() operation
    // Access: $event->getRepository(), $event->getModel()
    // Perfect for cache clearing, notifications, etc.
}

class RepositoryEntityDeleted extends RepositoryEventBase
{
    // Fired after successful delete() operation
    // Access: $event->getRepository(), $event->getModel()
    // Perfect for cleanup, logging, etc.
}
```

### Event Base Class

```php
abstract class RepositoryEventBase
{
    protected $model;
    protected RepositoryInterface $repository;
    protected string $action;

    public function __construct(RepositoryInterface $repository, $model)
    {
        $this->repository = $repository;
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    public function getAction(): string
    {
        return $this->action; // 'creating', 'created', 'updating', etc.
    }
}
```

## 👂 Event Listeners

### Basic Event Listener

```php
<?php

namespace App\Listeners;

use Apiato\Repository\Events\RepositoryEntityCreated;
use Illuminate\Support\Facades\Log;

/**
 * Log all repository create operations
 */
class LogRepositoryCreation
{
    public function handle(RepositoryEntityCreated $event)
    {
        $model = $event->getModel();
        $repository = $event->getRepository();
        
        Log::info('Entity Created', [
            'repository' => get_class($repository),
            'model' => get_class($model),
            'id' => $model->id,
            'data' => $model->toArray(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

### Advanced Event Listener with Business Logic

```php
<?php

namespace App\Listeners;

use Apiato\Repository\Events\RepositoryEntityUpdated;
use App\Models\User;
use App\Jobs\SendWelcomeEmail;
use App\Jobs\UpdateSearchIndex;
use Illuminate\Support\Facades\Cache;

/**
 * Handle user updates with complex business logic
 */
class HandleUserUpdated
{
    public function handle(RepositoryEntityUpdated $event)
    {
        $user = $event->getModel();
        $repository = $event->getRepository();
        
        // Only handle User model updates
        if (!$user instanceof User) {
            return;
        }
        
        // Check what changed
        $changes = $user->getChanges();
        
        // Handle email verification
        if (isset($changes['email_verified_at']) && $changes['email_verified_at']) {
            $this->handleEmailVerified($user);
        }
        
        // Handle status changes
        if (isset($changes['status'])) {
            $this->handleStatusChange($user, $changes['status']);
        }
        
        // Handle role changes
        if (isset($changes['role_id'])) {
            $this->handleRoleChange($user, $changes['role_id']);
        }
        
        // Update search index
        UpdateSearchIndex::dispatch($user);
        
        // Clear related caches
        $this->clearUserCaches($user);
    }
    
    protected function handleEmailVerified(User $user)
    {
        // Send welcome email
        SendWelcomeEmail::dispatch($user);
        
        // Grant verified user permissions
        $user->givePermissionTo('post_comments');
        $user->givePermissionTo('create_posts');
        
        // Log verification
        Log::info('User email verified', ['user_id' => $user->id]);
    }
    
    protected function handleStatusChange(User $user, $newStatus)
    {
        switch ($newStatus) {
            case 'suspended':
                // Revoke active sessions
                $user->tokens()->delete();
                // Notify administrators
                event(new UserSuspended($user));
                break;
                
            case 'active':
                // Send reactivation email
                Mail::to($user)->send(new AccountReactivated($user));
                break;
        }
    }
    
    protected function handleRoleChange(User $user, $newRoleId)
    {
        // Clear permission caches
        Cache::forget("user_permissions_{$user->id}");
        
        // Log role change for audit
        Log::info('User role changed', [
            'user_id' => $user->id,
            'new_role_id' => $newRoleId,
            'changed_by' => auth()->id(),
        ]);
    }
    
    protected function clearUserCaches(User $user)
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

### Event Listener Registration

```php
// app/Providers/EventServiceProvider.php
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Repository Events
        'Apiato\Repository\Events\RepositoryEntityCreated' => [
            'App\Listeners\LogRepositoryCreation',
            'App\Listeners\UpdateSearchIndex',
            'App\Listeners\ClearCache',
        ],
        
        'Apiato\Repository\Events\RepositoryEntityUpdated' => [
            'App\Listeners\HandleUserUpdated',
            'App\Listeners\InvalidateCache',
            'App\Listeners\TriggerWebhooks',
        ],
        
        'Apiato\Repository\Events\RepositoryEntityDeleted' => [
            'App\Listeners\LogRepositoryDeletion',
            'App\Listeners\CleanupRelatedData',
            'App\Listeners\NotifyDeletion',
        ],
        
        // Model-specific events
        'App\Events\UserCreated' => [
            'App\Listeners\SendWelcomeEmail',
            'App\Listeners\SetupUserDefaults',
        ],
    ];
}
```

## 🤖 Automatic Event Triggers

### Built-in Repository Events

```php
class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }
    
    // All these methods automatically trigger events:
    
    public function createUser(array $data)
    {
        // RepositoryEntityCreating fired automatically
        $user = $this->create($data);
        // RepositoryEntityCreated fired automatically
        
        return $user;
    }
    
    public function updateUser($id, array $data)
    {
        // RepositoryEntityUpdating fired automatically
        $user = $this->update($data, $id);
        // RepositoryEntityUpdated fired automatically
        
        return $user;
    }
    
    public function deleteUser($id)
    {
        // RepositoryEntityDeleting fired automatically
        $result = $this->delete($id);
        // RepositoryEntityDeleted fired automatically
        
        return $result;
    }
}
```

### Conditional Event Firing

```php
class ConditionalEventRepository extends BaseRepository
{
    /**
     * Create with conditional event firing
     */
    public function createWithoutEvents(array $data)
    {
        // Temporarily disable events
        $originalEvents = $this->enableEvents;
        $this->enableEvents = false;
        
        try {
            $model = $this->create($data);
            return $model;
        } finally {
            $this->enableEvents = $originalEvents;
        }
    }
    
    /**
     * Bulk operations with single event
     */
    public function bulkCreate(array $records)
    {
        // Disable individual events
        $this->enableEvents = false;
        
        $created = [];
        foreach ($records as $data) {
            $created[] = $this->create($data);
        }
        
        // Re-enable events and fire bulk event
        $this->enableEvents = true;
        event(new BulkRepositoryCreation($this, $created));
        
        return $created;
    }
}
```

### Event Filtering

```php
class FilteredEventRepository extends BaseRepository
{
    /**
     * Only fire events for significant changes
     */
    public function updateWithSignificanceCheck($id, array $data)
    {
        $model = $this->find($id);
        $originalData = $model->toArray();
        
        // Check if update is significant
        $significantFields = ['status', 'role_id', 'email', 'password'];
        $hasSignificantChanges = collect($data)
            ->keys()
            ->intersect($significantFields)
            ->isNotEmpty();
        
        if ($hasSignificantChanges) {
            // Fire events for significant changes
            return $this->update($data, $id);
        } else {
            // Skip events for minor changes
            return $this->skipEvents()->update($data, $id);
        }
    }
}
```

## 🔧 Custom Event Integration

### Custom Repository Events

```php
<?php

namespace App\Events;

use Apiato\Repository\Events\RepositoryEventBase;

/**
 * Custom event for user profile completion
 */
class UserProfileCompleted extends RepositoryEventBase
{
    protected string $action = "profile_completed";
    
    public function __construct($repository, $user, $completionPercentage)
    {
        parent::__construct($repository, $user);
        $this->completionPercentage = $completionPercentage;
    }
    
    public function getCompletionPercentage(): int
    {
        return $this->completionPercentage;
    }
}

/**
 * Custom event for business rule violations
 */
class BusinessRuleViolation extends RepositoryEventBase
{
    protected string $action = "business_rule_violation";
    
    public function __construct($repository, $model, $violation)
    {
        parent::__construct($repository, $model);
        $this->violation = $violation;
    }
    
    public function getViolation(): array
    {
        return $this->violation;
    }
}
```

### Repository with Custom Events

```php
class UserRepository extends BaseRepository
{
    public function updateProfile($userId, array $profileData)
    {
        $user = $this->update($profileData, $userId);
        
        // Calculate profile completion
        $completion = $this->calculateProfileCompletion($user);
        
        // Fire custom event if profile is now complete
        if ($completion >= 100) {
            event(new UserProfileCompleted($this, $user, $completion));
        }
        
        return $user;
    }
    
    public function create(array $attributes)
    {
        // Validate business rules before creation
        $violations = $this->validateBusinessRules($attributes);
        
        if (!empty($violations)) {
            event(new BusinessRuleViolation($this, null, $violations));
            throw new BusinessRuleException('Business rule violations detected');
        }
        
        return parent::create($attributes);
    }
    
    protected function calculateProfileCompletion($user): int
    {
        $requiredFields = ['name', 'email', 'phone', 'bio', 'avatar'];
        $completedFields = 0;
        
        foreach ($requiredFields as $field) {
            if (!empty($user->$field)) {
                $completedFields++;
            }
        }
        
        return round(($completedFields / count($requiredFields)) * 100);
    }
    
    protected function validateBusinessRules(array $data): array
    {
        $violations = [];
        
        // Example business rules
        if (isset($data['email']) && $this->isEmailBlacklisted($data['email'])) {
            $violations[] = 'Email domain is blacklisted';
        }
        
        if (isset($data['age']) && $data['age'] < 18) {
            $violations[] = 'Users must be 18 or older';
        }
        
        return $violations;
    }
}
```

### Event Middleware

```php
<?php

namespace App\Repository\Middleware;

/**
 * Middleware for repository events
 */
class RepositoryEventMiddleware
{
    protected $beforeMiddleware = [];
    protected $afterMiddleware = [];
    
    public function before($callback)
    {
        $this->beforeMiddleware[] = $callback;
        return $this;
    }
    
    public function after($callback)
    {
        $this->afterMiddleware[] = $callback;
        return $this;
    }
    
    public function handle($event, $next)
    {
        // Execute before middleware
        foreach ($this->beforeMiddleware as $middleware) {
            $result = $middleware($event);
            if ($result === false) {
                return false; // Cancel event
            }
        }
        
        // Execute main event
        $result = $next($event);
        
        // Execute after middleware
        foreach ($this->afterMiddleware as $middleware) {
            $middleware($event, $result);
        }
        
        return $result;
    }
}

// Usage in repository
class MiddlewareRepository extends BaseRepository
{
    protected function fireRepositoryEvent($eventClass, $model)
    {
        $middleware = new RepositoryEventMiddleware();
        
        $middleware
            ->before(function($event) {
                // Log event
                Log::info("Repository event: {$event->getAction()}");
            })
            ->after(function($event, $result) {
                // Clear cache
                Cache::tags(['repository_events'])->flush();
            });
        
        $event = new $eventClass($this, $model);
        
        return $middleware->handle($event, function($event) {
            return event($event);
        });
    }
}
```

## 🌍 Real-World Use Cases

### E-commerce Order Processing

```php
class OrderRepository extends BaseRepository
{
    public function createOrder(array $orderData)
    {
        $order = $this->create($orderData);
        
        // Order events are fired automatically, triggering:
        // - Inventory reservation
        // - Payment processing
        // - Customer notifications
        // - Analytics tracking
        
        return $order;
    }
}

// Listener for order created
class ProcessOrderCreated
{
    public function handle(RepositoryEntityCreated $event)
    {
        $order = $event->getModel();
        
        if (!$order instanceof Order) {
            return;
        }
        
        // Reserve inventory
        ReserveInventory::dispatch($order);
        
        // Process payment
        ProcessPayment::dispatch($order);
        
        // Send confirmation email
        SendOrderConfirmation::dispatch($order);
        
        // Update analytics
        TrackOrderCreated::dispatch($order);
        
        // Trigger webhooks
        TriggerOrderWebhooks::dispatch($order);
    }
}
```

### Content Management System

```php
class PostRepository extends BaseRepository
{
    public function publishPost($id)
    {
        $post = $this->update(['status' => 'published', 'published_at' => now()], $id);
        
        // Post update events trigger:
        // - Search index update
        // - Cache invalidation
        // - Social media sharing
        // - Subscriber notifications
        
        return $post;
    }
}

class HandlePostPublished
{
    public function handle(RepositoryEntityUpdated $event)
    {
        $post = $event->getModel();
        
        if (!$post instanceof Post || $post->status !== 'published') {
            return;
        }
        
        // Update search index
        UpdateElasticsearchIndex::dispatch($post);
        
        // Clear caches
        Cache::tags(['posts', 'published_posts'])->flush();
        
        // Share on social media
        ShareOnSocialMedia::dispatch($post);
        
        // Notify subscribers
        NotifySubscribers::dispatch($post);
        
        // Generate sitemap
        RegenerateSitemap::dispatch();
    }
}
```

### User Management & Analytics

```php
class UserRepository extends BaseRepository
{
    public function registerUser(array $userData)
    {
        $user = $this->create($userData);
        
        // User creation events trigger:
        // - Welcome email sequence
        // - Default settings setup
        // - Analytics tracking
        // - Third-party integrations
        
        return $user;
    }
}

class HandleUserRegistration
{
    public function handle(RepositoryEntityCreated $event)
    {
        $user = $event->getModel();
        
        if (!$user instanceof User) {
            return;
        }
        
        // Start welcome email sequence
        WelcomeEmailSequence::dispatch($user);
        
        // Setup default user settings
        SetupUserDefaults::dispatch($user);
        
        // Track registration in analytics
        AnalyticsService::track('user_registered', [
            'user_id' => $user->id,
            'registration_source' => request()->get('source'),
            'referrer' => request()->header('referer'),
        ]);
        
        // Sync with CRM
        SyncWithCRM::dispatch($user);
        
        // Create user profile
        CreateUserProfile::dispatch($user);
    }
}
```

### Audit Trail & Compliance

```php
class AuditableRepository extends BaseRepository
{
    // All operations automatically create audit trails
}

class AuditTrailListener
{
    public function handle($event)
    {
        $model = $event->getModel();
        $action = $event->getAction();
        
        // Create audit record
        AuditLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->id ?? null,
            'action' => $action,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $this->getOldValues($model),
            'new_values' => $this->getNewValues($model),
            'timestamp' => now(),
        ]);
        
        // Check compliance rules
        ComplianceChecker::check($model, $action);
    }
    
    protected function getOldValues($model): array
    {
        return method_exists($model, 'getOriginal') 
            ? $model->getOriginal() 
            : [];
    }
    
    protected function getNewValues($model): array
    {
        return method_exists($model, 'getAttributes') 
            ? $model->getAttributes() 
            : [];
    }
}
```

## ⚡ Performance Considerations

### Async Event Processing

```php
class AsyncEventRepository extends BaseRepository
{
    /**
     * Use queued listeners for heavy operations
     */
    protected function fireRepositoryEvent($eventClass, $model)
    {
        $event = new $eventClass($this, $model);
        
        // Fire synchronous events immediately
        $this->fireSyncEvents($event);
        
        // Queue heavy operations
        $this->queueAsyncEvents($event);
        
        return $event;
    }
    
    protected function fireSyncEvents($event)
    {
        // Only fire critical sync events
        $syncListeners = [
            'App\Listeners\ValidateBusinessRules',
            'App\Listeners\ClearCriticalCache',
        ];
        
        foreach ($syncListeners as $listener) {
            app($listener)->handle($event);
        }
    }
    
    protected function queueAsyncEvents($event)
    {
        // Queue non-critical operations
        ProcessRepositoryEvent::dispatch($event)
            ->onQueue('repository-events')
            ->delay(now()->addSeconds(5));
    }
}

// Queued job for async event processing
class ProcessRepositoryEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $event;
    
    public function __construct($event)
    {
        $this->event = $event;
    }
    
    public function handle()
    {
        $asyncListeners = [
            'App\Listeners\UpdateSearchIndex',
            'App\Listeners\SendNotifications',
            'App\Listeners\SyncWithThirdParties',
            'App\Listeners\GenerateReports',
        ];
        
        foreach ($asyncListeners as $listener) {
            try {
                app($listener)->handle($this->event);
            } catch (Exception $e) {
                Log::error("Async event listener failed: {$listener}", [
                    'error' => $e->getMessage(),
                    'event' => get_class($this->event),
                ]);
            }
        }
    }
}
```

### Event Batching

```php
class BatchedEventRepository extends BaseRepository
{
    protected $eventBatch = [];
    protected $batchSize = 100;
    
    /**
     * Batch events for bulk processing
     */
    protected function fireRepositoryEvent($eventClass, $model)
    {
        $this->eventBatch[] = [
            'event_class' => $eventClass,
            'model' => $model,
            'timestamp' => now(),
        ];
        
        if (count($this->eventBatch) >= $this->batchSize) {
            $this->processBatch();
        }
        
        return new $eventClass($this, $model);
    }
    
    protected function processBatch()
    {
        if (empty($this->eventBatch)) {
            return;
        }
        
        // Group events by type
        $groupedEvents = collect($this->eventBatch)
            ->groupBy('event_class');
        
        foreach ($groupedEvents as $eventClass => $events) {
            $models = $events->pluck('model');
            
            // Fire batch event
            event(new BatchRepositoryEvent($this, $eventClass, $models));
        }
        
        // Clear batch
        $this->eventBatch = [];
    }
    
    public function __destruct()
    {
        // Process remaining events
        $this->processBatch();
    }
}
```

### Selective Event Firing

```php
class SelectiveEventRepository extends BaseRepository
{
    protected $eventConfig = [
        'create' => ['sync' => true, 'async' => true],
        'update' => ['sync' => true, 'async' => false], // Only sync events
        'delete' => ['sync' => false, 'async' => true], // Only async events
    ];
    
    protected function shouldFireEvent($action, $type): bool
    {
        return $this->eventConfig[$action][$type] ?? false;
    }
    
    protected function fireRepositoryEvent($eventClass, $model)
    {
        $action = $this->getActionFromEventClass($eventClass);
        
        if ($this->shouldFireEvent($action, 'sync')) {
            // Fire synchronous event
            event(new $eventClass($this, $model));
        }
        
        if ($this->shouldFireEvent($action, 'async')) {
            // Queue asynchronous event
            ProcessRepositoryEvent::dispatch(new $eventClass($this, $model));
        }
    }
}
```

## 🏗️ Event-Driven Architecture

### Event Sourcing Integration

```php
class EventSourcedRepository extends BaseRepository
{
    /**
     * Store events for event sourcing
     */
    protected function fireRepositoryEvent($eventClass, $model)
    {
        $event = new $eventClass($this, $model);
        
        // Store event for event sourcing
        $this->storeEvent($event);
        
        // Fire event normally
        event($event);
        
        return $event;
    }
    
    protected function storeEvent($event)
    {
        EventStore::create([
            'aggregate_id' => $event->getModel()->id ?? null,
            'aggregate_type' => get_class($event->getModel()),
            'event_type' => get_class($event),
            'event_data' => json_encode($event->getModel()->toArray()),
            'metadata' => json_encode([
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
            'occurred_at' => now(),
        ]);
    }
    
    /**
     * Replay events to rebuild state
     */
    public function replayEvents($aggregateId)
    {
        $events = EventStore::where('aggregate_id', $aggregateId)
            ->orderBy('occurred_at')
            ->get();
        
        $model = null;
        
        foreach ($events as $storedEvent) {
            $eventData = json_decode($storedEvent->event_data, true);
            $model = $this->applyEvent($model, $storedEvent->event_type, $eventData);
        }
        
        return $model;
    }
}
```

### CQRS (Command Query Responsibility Segregation)

```php
class CQRSRepository extends BaseRepository
{
    protected $writeModel;
    protected $readModel;
    
    public function __construct($app)
    {
        parent::__construct($app);
        $this->writeModel = $this->model;
        $this->readModel = app($this->readModel());
    }
    
    /**
     * Write operations use write model and fire events
     */
    public function create(array $attributes)
    {
        $model = $this->writeModel->create($attributes);
        
        // Fire event for read model synchronization
        event(new ModelCreated($this, $model));
        
        return $model;
    }
    
    /**
     * Read operations use optimized read model
     */
    public function find($id, $columns = ['*'])
    {
        return $this->readModel->find($id, $columns);
    }
    
    public function paginate($limit = null, $columns = ['*'])
    {
        return $this->readModel->paginate($limit, $columns);
    }
    
    /**
     * Specify read model class
     */
    protected function readModel()
    {
        return str_replace('Repository', 'ReadModel', static::class);
    }
}

// Listener to sync read model
class SyncReadModel
{
    public function handle($event)
    {
        $writeModel = $event->getModel();
        $readModelClass = $event->getRepository()->readModel();
        
        // Update read model
        $readModelClass::updateOrCreate(
            ['id' => $writeModel->id],
            $this->transformForReadModel($writeModel)
        );
    }
    
    protected function transformForReadModel($model): array
    {
        // Transform write model data for read model optimization
        return $model->toArray();
    }
}
```

---

**Next:** Learn about **[Validation](validation.md)** for automatic data validation and business rule enforcement.