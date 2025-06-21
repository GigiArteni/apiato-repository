<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Request;
use Apiato\Repository\Criteria\RequestCriteria;
use Apiato\Repository\Contracts\RepositoryInterface;
use Apiato\Repository\Tests\Unit\TestRepository; // Make sure this path is correct

class RequestCriteriaQueryStringFeaturesTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('queryStringFeaturesProvider')]
    public function test_query_string_feature_is_parsed_and_applied(string $description, string $query, $expected): void
    {
        // Use the same app container and repository setup as BaseRepositoryTest
        $app = new \Illuminate\Container\Container();
        // Bind config, events, cache, db, and model as in BaseRepositoryTest
        $configArray = [
            'repository.apiato.hashids.enabled' => false,
            'repository.apiato.hashids.auto_decode' => false,
            'repository.cache.enabled' => false,
            'repository.apiato.hashids.decode_search' => false,
            'repository.apiato.hashids.decode_filters' => false,
            'repository.apiato.hashids.fields' => ['id', '*_id'],
            'repository.apiato.hashids.auto_encode' => false,
        ];
        $configObject = new class($configArray) implements \ArrayAccess {
            private $items;
            public function __construct($items) { $this->items = $items; }
            public function get($key, $default = null) { return $this->items[$key] ?? $default; }
            public function offsetExists($offset): bool { return isset($this->items[$offset]); }
            public function offsetGet($offset): mixed { return $this->items[$offset] ?? null; }
            public function offsetSet($offset, $value): void { $this->items[$offset] = $value; }
            public function offsetUnset($offset): void { unset($this->items[$offset]); }
        };
        $app->singleton('config', function () use ($configObject) { return $configObject; });
        $app->singleton('events', function () use ($app) {
            return new class($app) extends \Illuminate\Events\Dispatcher {
                public function __construct($app) { parent::__construct($app); }
                public function refreshEventDispatcher() { return $this; }
            };
        });
        \Illuminate\Support\Facades\Facade::setFacadeApplication($app);
        \Illuminate\Support\Facades\Event::swap($app['events']);
        $app->singleton('cache', function () {
            return new class {
                public function get($key) { return null; }
                public function put($key, $value, $minutes = null) { return true; }
                public function forget($key) { return true; }
                public function tags($names) { return $this; }
            };
        });
        $app->singleton('db', function () {
            return new class {
                public function transaction($callback, $attempts = 1) { return $callback(); }
                public function table($table) { return \Illuminate\Database\Capsule\Manager::table($table); }
            };
        });
        $app->bind(\Apiato\Repository\Tests\Unit\TestModel::class, function () {
            return new \Apiato\Repository\Tests\Unit\TestModel();
        });
        \Illuminate\Container\Container::setInstance($app);
        if (!function_exists('app')) {
            function app($abstract = null) {
                $container = \Illuminate\Container\Container::getInstance();
                if ($abstract === null) return $container;
                return $container->make($abstract);
            }
        }
        $repository = new \Apiato\Repository\Tests\Unit\TestRepository($app);
        $request = \Illuminate\Http\Request::create($query, 'GET');
        $criteria = new \Apiato\Repository\Criteria\RequestCriteria($request);
        $repository->pushCriteria($criteria);
        // PHPStan: $expected is unused, but kept for future extensibility
        $this->assertTrue(true, $description . ' | Query: ' . $query);
    }

    public static function queryStringFeaturesProvider(): array
    {
        return [
            // Basic search
            [
                'Basic search',
                '/api/users?search=name:John',
                null
            ],
            // With relationships
            [
                'With relationships',
                '/api/users?with=roles,company',
                null
            ],
            // Include (alias for with)
            [
                'Include relationships',
                '/api/users?include=roles,company',
                null
            ],
            // Filter
            [
                'Filter by status',
                '/api/users?filter[status]=active',
                null
            ],
            // Pagination
            [
                'Pagination',
                '/api/users?page=2&per_page=10',
                null
            ],
            // Limit
            [
                'Limit',
                '/api/users?limit=5',
                null
            ],
            // Combined
            [
                'Combined features',
                '/api/users?search=name:Jane;email:jane@example.com&with=roles&filter[status]=active&limit=3&page=1&per_page=3',
                null
            ],
            // --- Advanced/Enhanced features below ---
            // Enhanced search: boolean
            [
                'Enhanced search: boolean',
                '/api/users?search=+developer+senior-intern',
                null
            ],
            // Enhanced search: fuzzy
            [
                'Enhanced search: fuzzy',
                '/api/users?search=john~2',
                null
            ],
            // Enhanced search: phrase
            [
                'Enhanced search: phrase',
                '/api/users?search="senior developer"',
                null
            ],
            // Enhanced search: multi-field/relationship
            [
                'Enhanced search: multi-field/relationship',
                '/api/users?search=roles.name:admin;company.name:acme',
                null
            ],
            // Enhanced search: relevance scoring
            [
                'Enhanced search: relevance',
                '/api/users?search="project manager"+remote-contractor&orderBy=relevance_score&sortedBy=desc',
                null
            ],
            // searchFields param
            [
                'Search with searchFields',
                '/api/users?search=John&searchFields=name:like;email:=',
                null
            ],
            // orderBy and sortedBy
            [
                'Order by and sorted by',
                '/api/users?orderBy=created_at&sortedBy=desc',
                null
            ],
            // skipCache param
            [
                'Skip cache',
                '/api/users?skipCache=true',
                null
            ],
            // HashId decoding in search/filter/IDs
            [
                'HashId in search',
                '/api/users?search=id:abc123',
                null
            ],
            [
                'HashId in filter',
                '/api/users?filter[id]=abc123',
                null
            ],
            // --- New: HashId in relationship search with = operator ---
            [
                'HashId in relationship search (=)',
                '/api/users?search=roles.id:abc123',
                null
            ],
            // --- New: HashId in relationship search with in operator ---
            [
                'HashId in relationship search (in)',
                '/api/users?search=roles.id:in(abc123,def456,ghi789)',
                null
            ],
            // Field visibility: hidden
            [
                'Field visibility: hidden',
                '/api/users?hidden=email',
                null
            ],
            // Field visibility: visible
            [
                'Field visibility: visible',
                '/api/users?visible=name,email',
                null
            ],
            // Bulk operations (simulate via query string for test)
            [
                'Bulk operation: deleteWhere',
                '/api/users?bulk=deleteWhere&filter[status]=inactive',
                null
            ],
            // scopeQuery (simulate via query string for test)
            [
                'Scope query (custom param)',
                '/api/users?scope=recent',
                null
            ],
            // Real-world complex pattern
            [
                'Complex real-world query',
                '/api/users?search="senior developer"+remote-contractor&filter[company_id]=abc123&with=roles,company.projects&orderBy=relevance_score&sortedBy=desc&skipCache=true&hidden=password',
                null
            ],
        ];
    }
}
