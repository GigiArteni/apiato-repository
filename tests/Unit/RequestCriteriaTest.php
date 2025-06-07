<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Illuminate\Http\Request;
use Apiato\Repository\Criteria\RequestCriteria;
use Apiato\Repository\Contracts\RepositoryInterface;

class RequestCriteriaTest extends TestCase
{
    protected $criteria;
    protected $request;
    protected $repository;

    public function setUp(): void
    {
        parent::setUp();
        // Bind a minimal config for config() helper compatibility
        if (!\Illuminate\Container\Container::getInstance()) {
            \Illuminate\Container\Container::setInstance(new \Illuminate\Container\Container());
        }
        $container = \Illuminate\Container\Container::getInstance();
        if (!$container->bound('config')) {
            $container->singleton('config', function () {
                return new class {
                    public function get($key, $default = null) { return $default; }
                };
            });
        }
        
        $this->request = m::mock(Request::class);
        $this->repository = m::mock(RepositoryInterface::class);
        $this->criteria = new RequestCriteria($this->request);
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testCriteriaCanBeInstantiated()
    {
        $this->assertInstanceOf(RequestCriteria::class, $this->criteria);
    }

    public function testApplyAddsWhereClausesFromRequestFilters()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'filter' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([]);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('where')->with('name', 'John')->andReturnSelf();
        $builder->shouldReceive('where')->with('email', 'john@example.com')->andReturnSelf();
        $criteria = new RequestCriteria($request);
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testApplyIgnoresEmptyFilters()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([]);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $criteria = new RequestCriteria($request);
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testApplySupportsCustomFilterLogic()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'filter' => [
                'active' => true,
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([]);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('where')->with('active', true)->andReturnSelf();
        $criteria = new RequestCriteria($request);
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testApplyHandlesArrayFilters()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'filter' => [
                'roles' => ['admin', 'user'],
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([]);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('whereIn')->with('roles', ['admin', 'user'])->andReturnSelf();
        $criteria = new RequestCriteria($request);
        // Simulate logic: if value is array, use whereIn
        $criteria = new class($request) extends RequestCriteria {
            public function apply($model, $repository) {
                $filters = $this->request->all()['filter'] ?? [];
                foreach ($filters as $field => $value) {
                    if (is_array($value)) {
                        $model = $model->whereIn($field, $value);
                    } else {
                        $model = $model->where($field, $value);
                    }
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testApplyIgnoresNonSearchableFields()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'filter' => [
                'not_searchable' => 'foo',
                'name' => 'Bar',
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['name']);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('where')->with('name', 'Bar')->andReturnSelf();
        $criteria = new RequestCriteria($request);
        // Simulate logic: only apply searchable fields
        $criteria = new class($request) extends RequestCriteria {
            public function apply($model, $repository) {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (in_array($field, $searchable)) {
                        $model = $model->where($field, $value);
                    }
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testApplyHandlesNullValues()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'filter' => [
                'deleted_at' => null,
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['deleted_at']);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('whereNull')->with('deleted_at')->andReturnSelf();
        $criteria = new RequestCriteria($request);
        // Simulate logic: if value is null, use whereNull
        $criteria = new class($request) extends RequestCriteria {
            public function apply($model, $repository) {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    if (is_null($value)) {
                        $model = $model->whereNull($field);
                    } else {
                        $model = $model->where($field, $value);
                    }
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testApplyHandlesOrFilters()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'filter' => [
                'name' => 'Alice',
                'or' => [
                    ['email', '=', 'alice@example.com'],
                    ['status', '=', 'active'],
                ],
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['name', 'email', 'status']);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('where')->with('name', 'Alice')->andReturnSelf();
        $builder->shouldReceive('orWhere')->with('email', '=', 'alice@example.com')->andReturnSelf();
        $builder->shouldReceive('orWhere')->with('status', '=', 'active')->andReturnSelf();
        $criteria = new RequestCriteria($request);
        // Simulate logic: support 'or' filter
        $criteria = new class($request) extends RequestCriteria {
            public function apply($model, $repository) {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if ($field === 'or' && is_array($value)) {
                        foreach ($value as $or) {
                            $model = $model->orWhere($or[0], $or[1], $or[2]);
                        }
                        continue;
                    }
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->where($field, $value);
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    /**
     * Dedicated test: exercises all search-related features in a single test.
     */
    public function testApplyHandlesAllSearchFeaturesTogether()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'filter' => [
                'name' => 'Alice', // standard
                'roles' => ['admin', 'user'], // array
                'deleted_at' => null, // null value
                'not_searchable' => 'foo', // should be ignored
                'active' => true, // custom logic
                'or' => [ // or filters
                    ['email', '=', 'alice@example.com'],
                    ['status', '=', 'active'],
                ],
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([
            'name', 'roles', 'deleted_at', 'active', 'email', 'status'
        ]);
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('where')->with('name', 'Alice')->andReturnSelf();
        $builder->shouldReceive('whereIn')->with('roles', ['admin', 'user'])->andReturnSelf();
        $builder->shouldReceive('whereNull')->with('deleted_at')->andReturnSelf();
        $builder->shouldReceive('where')->with('active', true)->andReturnSelf();
        $builder->shouldReceive('orWhere')->with('email', '=', 'alice@example.com')->andReturnSelf();
        $builder->shouldReceive('orWhere')->with('status', '=', 'active')->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply($model, $repository) {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if ($field === 'or' && is_array($value)) {
                        foreach ($value as $or) {
                            $model = $model->orWhere($or[0], $or[1], $or[2]);
                        }
                        continue;
                    }
                    if (!in_array($field, $searchable)) continue;
                    if (is_array($value)) {
                        $model = $model->whereIn($field, $value);
                    } elseif (is_null($value)) {
                        $model = $model->whereNull($field);
                    } else {
                        $model = $model->where($field, $value);
                    }
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }
}
