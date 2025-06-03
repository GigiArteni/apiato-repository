# Testing Guide

Learn how to effectively test your repositories, criteria, presenters, and HashId functionality.

## Testing Setup

### Base Test Configuration

```php
<?php

namespace Tests;

use Apiato\Repository\Providers\RepositoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            RepositoryServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Database configuration
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Repository configuration
        $app['config']->set('repository.cache.enabled', false);
        $app['config']->set('repository.hashid.enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup
        $this->artisan('migrate');
    }
}
```

### Factory Setup

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->unique()->userName(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

## Repository Testing

### Basic Repository Tests

```php
<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_can_create_user(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ];

        $user = $this->repository->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_can_find_user_by_id(): void
    {
        $user = User::factory()->create();

        $found = $this->repository->find($user->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals($user->name, $found->name);
    }

    public function test_returns_null_when_user_not_found(): void
    {
        $found = $this->repository->find(999);

        $this->assertNull($found);
    }

    public function test_find_or_fail_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findOrFail(999);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create();
        $newData = ['name' => 'Updated Name'];

        $updated = $this->repository->update($newData, $user->id);

        $this->assertEquals($newData['name'], $updated->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newData['name'],
        ]);
    }

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->delete($user->id);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_can_paginate_users(): void
    {
        User::factory()->count(25)->create();

        $results = $this->repository->paginate(10);

        $this->assertEquals(10, $results->count());
        $this->assertEquals(25, $results->total());
        $this->assertEquals(3, $results->lastPage());
    }

    public function test_can_find_by_field(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $results = $this->repository->findByField('status', 'active');

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->id);
    }

    public function test_can_find_where_in(): void
    {
        $users = User::factory()->count(3)->create();
        $ids = $users->pluck('id')->toArray();

        $results = $this->repository->findWhereIn('id', $ids);

        $this->assertCount(3, $results);
        $this->assertEquals($ids, $results->pluck('id')->sort()->values()->toArray());
    }

    public function test_can_find_where_between(): void
    {
        $start = now()->subDays(5);
        $end = now()->subDays(1);
        
        // Create users outside the range
        User::factory()->create(['created_at' => now()->subDays(10)]);
        User::factory()->create(['created_at' => now()]);
        
        // Create users within the range
        $userInRange = User::factory()->create(['created_at' => now()->subDays(3)]);

        $results = $this->repository->findWhereBetween('created_at', [$start, $end]);

        $this->assertCount(1, $results);
        $this->assertEquals($userInRange->id, $results->first()->id);
    }
}
```

### Repository with Relationships

```php
class UserRepositoryRelationshipTest extends TestCase
{
    public function test_can_find_users_with_posts(): void
    {
        $userWithPosts = User::factory()->hasPosts(3)->create();
        $userWithoutPosts = User::factory()->create();

        $results = $this->repository->query()
            ->whereHas('posts')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($userWithPosts->id, $results->first()->id);
    }

    public function test_can_eager_load_relationships(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(2)->create();

        $found = $this->repository->query()
            ->with(['profile', 'posts'])
            ->find($user->id);

        $this->assertTrue($found->relationLoaded('profile'));
        $this->assertTrue($found->relationLoaded('posts'));
        $this->assertCount(2, $found->posts);
    }
}
```

## HashId Repository Testing

### HashId Functionality Tests

```php
<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class HashIdRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_can_encode_hash_id(): void
    {
        $id = 123;
        $hashId = $this->repository->encodeHashId($id);

        $this->assertIsString($hashId);
        $this->assertNotEquals($id, $hashId);
        $this->assertGreaterThan(0, strlen($hashId));
    }

    public function test_can_decode_hash_id(): void
    {
        $id = 123;
        $hashId = $this->repository->encodeHashId($id);
        $decodedId = $this->repository->decodeHashId($hashId);

        $this->assertEquals($id, $decodedId);
    }

    public function test_decode_returns_null_for_invalid_hash_id(): void
    {
        $result = $this->repository->decodeHashId('invalid');

        $this->assertNull($result);
    }

    public function test_can_find_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $found = $this->repository->findByHashId($hashId);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_by_hash_id_returns_null_for_invalid_id(): void
    {
        $found = $this->repository->findByHashId('invalid');

        $this->assertNull($found);
    }

    public function test_can_find_by_hash_id_or_fail(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $found = $this->repository->findByHashIdOrFail($hashId);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_by_hash_id_or_fail_throws_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findByHashIdOrFail('invalid');
    }

    public function test_can_update_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);
        $newData = ['name' => 'Updated Name'];

        $updated = $this->repository->updateByHashId($newData, $hashId);

        $this->assertEquals($newData['name'], $updated->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newData['name'],
        ]);
    }

    public function test_can_delete_by_hash_id(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $result = $this->repository->deleteByHashId($hashId);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_looks_like_hash_id_detection(): void
    {
        $this->assertTrue($this->repository->looksLikeHashId('abc123'));
        $this->assertTrue($this->repository->looksLikeHashId('XyZ789'));
        $this->assertFalse($this->repository->looksLikeHashId('123'));
        $this->assertFalse($this->repository->looksLikeHashId('abc'));
        $this->assertFalse($this->repository->looksLikeHashId(''));
    }
}
```

## Criteria Testing

### Basic Criteria Tests

```php
<?php

namespace Tests\Unit\Criteria;

use App\Criteria\ActiveUsersCriteria;
use App\Models\User;
use App\Repositories\UserRepository;
use Tests\TestCase;

class ActiveUsersCriteriaTest extends TestCase
{
    public function test_applies_active_users_filter(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);
        User::factory()->create(['status' => 'banned']);

        $repository = app(UserRepository::class);
        $criteria = new ActiveUsersCriteria();

        $results = $repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
    }

    public function test_criteria_can_be_chained(): void
    {
        User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        User::factory()->create(['status' => 'active', 'email_verified_at' => null]);
        User::factory()->create(['status' => 'inactive', 'email_verified_at' => now()]);

        $repository = app(UserRepository::class);

        $results = $repository
            ->pushCriteria(new ActiveUsersCriteria())
            ->pushCriteria(new VerifiedUsersCriteria())
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
        $this->assertNotNull($results->first()->email_verified_at);
    }

    public function test_can_skip_criteria(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $repository = app(UserRepository::class);

        $results = $repository
            ->pushCriteria(new ActiveUsersCriteria())
            ->skipCriteria()
            ->all();

        $this->assertCount(2, $results);
    }

    public function test_can_clear_criteria(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $repository = app(UserRepository::class);

        $results = $repository
            ->pushCriteria(new ActiveUsersCriteria())
            ->clearCriteria()
            ->all();

        $this->assertCount(2, $results);
    }
}
```

### Request Criteria Tests

```php
<?php

namespace Tests\Unit\Criteria;

use Apiato\Repository\Criteria\RequestCriteria;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequestCriteriaTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepository::class);
    }

    public function test_applies_search_criteria(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $request = Request::create('/', 'GET', ['search' => 'name:John']);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);
    }

    public function test_applies_filter_criteria(): void
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $request = Request::create('/', 'GET', ['filter' => 'status:active']);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
    }

    public function test_applies_order_by_criteria(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        $user3 = User::factory()->create(['name' => 'Charlie']);

        $request = Request::create('/', 'GET', [
            'orderBy' => 'name',
            'sortedBy' => 'asc'
        ]);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $results->pluck('name')->toArray());
    }

    public function test_applies_includes(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(2)->create();

        $request = Request::create('/', 'GET', ['include' => 'profile,posts']);
        $criteria = new RequestCriteria($request);

        $result = $this->repository
            ->pushCriteria($criteria)
            ->find($user->id);

        $this->assertTrue($result->relationLoaded('profile'));
        $this->assertTrue($result->relationLoaded('posts'));
    }

    public function test_handles_hash_id_search(): void
    {
        $user = User::factory()->create();
        $hashId = $this->repository->encodeHashId($user->id);

        $request = Request::create('/', 'GET', ['search' => "id:{$hashId}"]);
        $criteria = new RequestCriteria($request);

        $results = $this->repository
            ->pushCriteria($criteria)
            ->all();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->id);
    }
}
```

## Caching Tests

### Cache Functionality Tests

```php
<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheableRepositoryTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable caching for tests
        config(['repository.cache.enabled' => true]);
        
        $this->repository = app(UserRepository::class);
    }

    public function test_repository_caches_results(): void
    {
        $user = User::factory()->create();
        
        // Mock cache expectations
        Cache::shouldReceive('tags')
            ->with(['users'])
            ->twice()
            ->andReturnSelf();
            
        Cache::shouldReceive('remember')
            ->twice()
            ->andReturn($user);

        // First call should hit cache
        $result1 = $this->repository->find($user->id);
        
        // Second call should also hit cache
        $result2 = $this->repository->find($user->id);

        $this->assertEquals($user->id, $result1->id);
        $this->assertEquals($user->id, $result2->id);
    }

    public function test_cache_is_cleared_on_write_operations(): void
    {
        Cache::shouldReceive('tags')
            ->with(['users'])
            ->andReturnSelf();
            
        Cache::shouldReceive('flush')
            ->once();

        $this->repository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_can_skip_cache(): void
    {
        $user = User::factory()->create();

        // When skipping cache, should not interact with cache
        Cache::shouldNotReceive('tags');
        Cache::shouldNotReceive('remember');

        $result = $this->repository
            ->skipCache()
            ->find($user->id);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_can_set_custom_cache_minutes(): void
    {
        $user = User::factory()->create();

        Cache::shouldReceive('tags')
            ->with(['users'])
            ->andReturnSelf();
            
        Cache::shouldReceive('remember')
            ->with(anything(), 120, anything()) // 120 minutes
            ->andReturn($user);

        $this->repository
            ->cacheMinutes(120)
            ->find($user->id);
    }

    public function test_generates_unique_cache_keys(): void
    {
        $key1 = $this->repository->getCacheKey('find', [1]);
        $key2 = $this->repository->getCacheKey('find', [2]);
        $key3 = $this->repository->getCacheKey('all', []);

        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertNotEquals($key2, $key3);
    }
}
```

## Presenter Testing

### Transformer Tests

```php
<?php

namespace Tests\Unit\Transformers;

use App\Models\User;
use App\Transformers\UserTransformer;
use Tests\TestCase;

class UserTransformerTest extends TestCase
{
    protected UserTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new UserTransformer();
    }

    public function test_transforms_user_data_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
        ]);

        $result = $this->transformer->transform($user);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);

        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals('johndoe', $result['username']);
    }

    public function test_encodes_hash_ids(): void
    {
        $user = User::factory()->create();
        $result = $this->transformer->transform($user);

        $this->assertNotEquals($user->id, $result['id']);
        $this->assertIsString($result['id']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result['id']);
    }

    public function test_formats_dates_correctly(): void
    {
        $user = User::factory()->create();
        $result = $this->transformer->transform($user);

        $this->assertStringContainsString('T', $result['created_at']);
        $this->assertStringContainsString('Z', $result['created_at']);
        
        // Verify it's a valid ISO 8601 date
        $this->assertNotFalse(\DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $result['created_at']));
    }
}
```

### Presenter Integration Tests

```php
<?php

namespace Tests\Unit\Presenters;

use App\Models\User;
use App\Presenters\UserPresenter;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Manager;
use Tests\TestCase;

class UserPresenterTest extends TestCase
{
    protected UserPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new UserPresenter();
    }

    public function test_presents_single_user(): void
    {
        $user = User::factory()->create();
        
        $result = $this->presenter->present($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('id', $result['data']);
        $this->assertArrayHasKey('name', $result['data']);
    }

    public function test_presents_user_collection(): void
    {
        $users = User::factory()->count(3)->create();
        
        $result = $this->presenter->present($users);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(3, $result['data']);
        
        foreach ($result['data'] as $userData) {
            $this->assertArrayHasKey('id', $userData);
            $this->assertArrayHasKey('name', $userData);
        }
    }

    public function test_presents_paginated_collection(): void
    {
        User::factory()->count(25)->create();
        
        $paginator = User::paginate(10);
        $result = $this->presenter->present($paginator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('pagination', $result['meta']);
        
        $pagination = $result['meta']['pagination'];
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(3, $pagination['last_page']);
    }
}
```

## Integration Tests

### Full API Integration Tests

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_user_list_with_filters(): void
    {
        User::factory()->create(['status' => 'active', 'name' => 'John Doe']);
        User::factory()->create(['status' => 'inactive', 'name' => 'Jane Smith']);
        User::factory()->create(['status' => 'active', 'name' => 'Bob Johnson']);

        $response = $this->getJson('/api/users?search=status:active&orderBy=name');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        
        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Bob Johnson', 'John Doe'], $names);
    }

    public function test_can_get_user_with_includes(): void
    {
        $user = User::factory()->hasProfile()->hasPosts(2)->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);

        $response = $this->getJson("/api/users/{$hashId}?include=profile,posts");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'profile' => ['data'],
                'posts' => ['data' => [['id', 'title']]],
            ]
        ]);
    }

    public function test_can_create_user_via_api(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'created_at']
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_can_update_user_via_api(): void
    {
        $user = User::factory()->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/users/{$hashId}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => ['name' => 'Updated Name']
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_user_via_api(): void
    {
        $user = User::factory()->create();
        $repository = app(UserRepository::class);
        $hashId = $repository->encodeHashId($user->id);

        $response = $this->deleteJson("/api/users/{$hashId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_returns_404_for_invalid_hash_id(): void
    {
        $response = $this->getJson('/api/users/invalid-hash-id');

        $response->assertStatus(404);
    }
}
```

## Performance Testing

### Database Query Testing

```php
<?php

namespace Tests\Performance;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepositoryPerformanceTest extends TestCase
{
    public function test_eager_loading_reduces_queries(): void
    {
        // Create test data
        $users = User::factory()->count(5)->hasProfile()->hasPosts(3)->create();

        DB::enableQueryLog();

        // Without eager loading - should cause N+1 problem
        $repository = app(UserRepository::class);
        $results = $repository->all();
        
        foreach ($results as $user) {
            $user->profile; // This will trigger additional queries
            $user->posts;   // This will trigger additional queries
        }

        $queriesWithoutEagerLoading = count(DB::getQueryLog());
        DB::flushQueryLog();

        // With eager loading
        $results = $repository->query()->with(['profile', 'posts'])->get();
        
        foreach ($results as $user) {
            $user->profile; // No additional query
            $user->posts;   // No additional query
        }

        $queriesWithEagerLoading = count(DB::getQueryLog());

        $this->assertLessThan($queriesWithoutEagerLoading, $queriesWithEagerLoading);
    }

    public function test_pagination_performance(): void
    {
        User::factory()->count(1000)->create();

        $start = microtime(true);
        
        $repository = app(UserRepository::class);
        $results = $repository->paginate(50);

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration); // Should complete in under 1 second
        $this->assertEquals(50, $results->count());
        $this->assertEquals(1000, $results->total());
    }

    public function test_cache_improves_performance(): void
    {
        config(['repository.cache.enabled' => true]);
        
        User::factory()->count(100)->create();
        $repository = app(UserRepository::class);

        // First call - hits database
        $start = microtime(true);
        $results1 = $repository->all();
        $firstCallDuration = microtime(true) - $start;

        // Second call - hits cache
        $start = microtime(true);
        $results2 = $repository->all();
        $secondCallDuration = microtime(true) - $start;

        $this->assertLessThan($firstCallDuration, $secondCallDuration);
        $this->assertEquals($results1->count(), $results2->count());
    }
}
```

## Test Data Builders

### Advanced Factory Usage

```php
<?php

namespace Tests\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserBuilder
{
    private array $attributes = [];
    private array $relationships = [];

    public static function create(): self
    {
        return new self();
    }

    public function active(): self
    {
        $this->attributes['status'] = 'active';
        return $this;
    }

    public function inactive(): self
    {
        $this->attributes['status'] = 'inactive';
        return $this;
    }

    public function verified(): self
    {
        $this->attributes['email_verified_at'] = now();
        return $this;
    }

    public function withProfile(array $profileData = []): self
    {
        $this->relationships['profile'] = $profileData;
        return $this;
    }

    public function withPosts(int $count = 3, array $postData = []): self
    {
        $this->relationships['posts'] = ['count' => $count, 'data' => $postData];
        return $this;
    }

    public function admin(): self
    {
        $this->relationships['role'] = ['name' => 'admin'];
        return $this;
    }

    public function build(): User
    {
        $user = User::factory()->create($this->attributes);

        foreach ($this->relationships as $relation => $data) {
            switch ($relation) {
                case 'profile':
                    $user->profile()->create($data);
                    break;
                    
                case 'posts':
                    Post::factory()->count($data['count'])->create(
                        array_merge(['user_id' => $user->id], $data['data'])
                    );
                    break;
                    
                case 'role':
                    $role = Role::firstOrCreate(['name' => $data['name']]);
                    $user->roles()->attach($role);
                    break;
            }
        }

        return $user->fresh();
    }

    public function buildMany(int $count): Collection
    {
        return collect(range(1, $count))->map(fn() => $this->build());
    }
}

// Usage in tests
class ExampleTest extends TestCase
{
    public function test_admin_users_can_access_dashboard(): void
    {
        $admin = UserBuilder::create()
            ->active()
            ->verified()
            ->admin()
            ->withProfile(['department' => 'IT'])
            ->build();

        $this->actingAs($admin);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertStatus(200);
    }
}
```

## Continuous Integration

### GitHub Actions Test Configuration

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.1, 8.2, 8.3]
        laravel-version: [11.x, 12.x]
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, dom, fileinfo, sqlite3
        coverage: xdebug
    
    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-
    
    - name: Install dependencies
      run: |
        composer require "illuminate/framework:${{ matrix.laravel-version }}" --no-interaction --no-update
        composer install --prefer-dist --no-interaction
    
    - name: Create SQLite database
      run: |
        mkdir -p database
        touch database/database.sqlite
    
    - name: Copy environment file
      run: cp .env.testing .env
    
    - name: Generate application key
      run: php artisan key:generate
    
    - name: Run tests
      run: vendor/bin/phpunit --coverage-clover coverage.xml
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        files: ./coverage.xml
        fail_ci_if_error: true
```

## Best Practices

### 1. Test Organization

```php
// Good: Organized test structure
tests/
├── Unit/
│   ├── Repositories/
│   ├── Criteria/
│   ├── Transformers/
│   └── Presenters/
├── Feature/
│   ├── Api/
│   └── Web/
├── Integration/
│   └── FullStack/
└── Performance/
    └── Benchmarks/
```

### 2. Test Naming

```php
// Good: Descriptive test names
public function test_can_find_active_users_with_posts(): void
public function test_throws_exception_when_hash_id_is_invalid(): void
public function test_caches_repository_results_for_configured_duration(): void

// Bad: Vague test names
public function test_find(): void
public function test_hash_id(): void
public function test_cache(): void
```

### 3. Assertion Quality

```php
// Good: Specific assertions
$this->assertInstanceOf(User::class, $user);
$this->assertEquals('active', $user->status);
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertJsonStructure(['data' => ['id', 'name', 'email']]);

// Bad: Generic assertions
$this->assertTrue($user instanceof User);
$this->assertTrue($user->status == 'active');
$this->assertTrue(User::where('email', 'test@example.com')->exists());
```

### 4. Data Cleanup

```php
public function tearDown(): void
{
    // Clear any singleton instances
    app()->forgetInstance(UserRepository::class);
    
    // Clear cache
    Cache::flush();
    
    parent::tearDown();
}
```

### 5. Mock Usage

```php
// Good: Mock external dependencies
public function test_sends_notification_on_user_creation(): void
{
    $this->mock(NotificationService::class)
         ->shouldReceive('send')
         ->once()
         ->with(Mockery::type(User::class));

    $this->repository->create(['name' => 'Test', 'email' => 'test@example.com']);
}

// Bad: Testing external services
public function test_actually_sends_email(): void
{
    Mail::fake(); // This is better, but still testing external behavior
    
    $this->repository->create(['name' => 'Test', 'email' => 'test@example.com']);
    
    Mail::assertSent(WelcomeEmail::class);
}
```

## Troubleshooting Tests

### Common Test Issues

**1. Memory issues with large datasets**
```php
// Use chunking for large datasets
User::factory()->count(10000)->create();

// Better: Create in chunks
collect(range(1, 100))->each(function () {
    User::factory()->count(100)->create();
});
```

**2. Database transaction issues**
```php
// Ensure proper transaction handling
public function test_rollback_on_error(): void
{
    DB::beginTransaction();
    
    try {
        $this->repository->create(['invalid' => 'data']);
        $this->fail('Should have thrown exception');
    } catch (\Exception $e) {
        DB::rollback();
        $this->assertDatabaseEmpty('users');
    }
}
```

**3. Cache interference between tests**
```php
protected function setUp(): void
{
    parent::setUp();
    Cache::flush(); // Clear cache before each test
}
```

## Next Steps

- Review [Installation Guide](installation.md) for setup
- Explore [Repository Usage](repositories.md) for implementation
- Check [Caching Strategy](caching.md) for performance optimization
- Learn [HashId Integration](hashids.md) for security features
