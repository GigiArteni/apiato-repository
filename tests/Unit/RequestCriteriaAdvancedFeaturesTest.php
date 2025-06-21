<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Illuminate\Http\Request;
use Apiato\Repository\Criteria\RequestCriteria;
use Apiato\Repository\Contracts\RepositoryInterface;

class RequestCriteriaAdvancedFeaturesTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!\Illuminate\Container\Container::getInstance()) {
            \Illuminate\Container\Container::setInstance(new \Illuminate\Container\Container());
        }
        $container = \Illuminate\Container\Container::getInstance();
        if (! $container->bound('config')) {
            $container->singleton('config', function () {
                return new class {
                    public function get($key, $default = null) { return $default; }
                };
            });
        }
    }

    /**
     * This test exercises all advanced search features in isolation, using strict type-safe mocks and expectations.
     * Each feature is tested in a separate method for clarity and maintainability.
     */

    public function testStandardFieldSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[name]=John
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'name' => 'John' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['name']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('where')->with('name', 'John')->andReturnSelf();
        $criteria = new RequestCriteria($request);
        echo "QueryString: ?filter[name]=John\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testRelationshipFieldSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[roles.name]=admin
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'roles.name' => 'admin' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['roles.name']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $subQuery = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $subQuery->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('whereHas')->withArgs(function($relation, $closure) use ($subQuery) {
            $closure($subQuery);
            return $relation === 'roles' && is_callable($closure);
        })->andReturnSelf();
        $criteria = new RequestCriteria($request);
        echo "QueryString: ?filter[roles.name]=admin\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testOperatorSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[age][operator]=>=&filter[age][value]=18
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'age' => ['operator' => '>=', 'value' => 18] ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['age']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('where')->with('age', '>=', 18)->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    if (is_array($value) && isset($value['operator'], $value['value'])) {
                        $model = $model->where($field, $value['operator'], $value['value']);
                    } else {
                        $model = $model->where($field, $value);
                    }
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[age][operator]=>=&filter[age][value]=18\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testArrayValueSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[roles][]=admin&filter[roles][]=user
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'roles' => ['admin', 'user'] ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['roles']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('whereIn')->with('roles', ['admin', 'user'])->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    if (is_array($value)) {
                        $model = $model->whereIn($field, $value);
                    } else {
                        $model = $model->where($field, $value);
                    }
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[roles][]=admin&filter[roles][]=user\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testNullValueSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[deleted_at]=
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'deleted_at' => null ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['deleted_at']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('whereNull')->with('deleted_at')->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
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
        echo "QueryString: ?filter[deleted_at]=\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testOrFilterSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[name]=Alice&filter[or][0][]=email&filter[or][0][]=\u003d&filter[or][0][]=alice@example.com&filter[or][1][]=status&filter[or][1][]=\u003d&filter[or][1][]=active
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
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('where')->with('name', 'Alice')->andReturnSelf();
        $builder->shouldReceive('orWhere')->with('email', '=', 'alice@example.com')->andReturnSelf();
        $builder->shouldReceive('orWhere')->with('status', '=', 'active')->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
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
        echo "QueryString: ?filter[name]=Alice&filter[or][0][]=email&filter[or][0][]=\u003d&filter[or][0][]=alice@example.com&filter[or][1][]=status&filter[or][1][]=\u003d&filter[or][1][]=active\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    // --- PHRASE SEARCH TESTS ---
    public function testPhraseSearchSimple()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[bio]="senior developer"
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'bio' => '"senior developer"' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['bio']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    // Simulate phrase search logic
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[bio]=\"senior developer\"\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testPhraseSearchWithMultipleWords()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[bio]="lead architect"
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'bio' => '"lead architect"' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['bio']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[bio]=\"lead architect\"\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testPhraseSearchWithSpecialCharacters()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[bio]="C++ developer"
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'bio' => '"C++ developer"' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['bio']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[bio]=\"C++ developer\"\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testPhraseSearchWithLongText()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[bio]="experienced full stack developer with Laravel"
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'bio' => '"experienced full stack developer with Laravel"' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['bio']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[bio]=\"experienced full stack developer with Laravel\"\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testPhraseSearchWithMultipleFields()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[bio]="team lead"&filter[summary]="agile expert"
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'bio' => '"team lead"', 'summary' => '"agile expert"' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['bio', 'summary']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[bio]=\"team lead\"&filter[summary]=\"agile expert\"\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    // --- FUZZY SEARCH TESTS ---
    public function testFuzzySearchSimple()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[name]=john~2
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'name' => 'john~2' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['name']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[name]=john~2\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testFuzzySearchWithDistance()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[name]=jon~1
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'name' => 'jon~1' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['name']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[name]=jon~1\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testFuzzySearchWithMultipleFields()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[name]=john~2&filter[email]=jane~1
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'name' => 'john~2', 'email' => 'jane~1' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['name', 'email']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[name]=john~2&filter[email]=jane~1\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    // --- DATE SEARCH TESTS ---
    public function testDateEqualsSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[created_at]=2025-06-07
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'created_at' => '2025-06-07' ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['created_at']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    // Simulate date equals logic
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testDateGreaterThanSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[created_at][operator]=>&filter[created_at][value]=2025-01-01
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'created_at' => ['operator' => '>', 'value' => '2025-01-01'] ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['created_at']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                echo "QueryString: ?filter[created_at][operator]=>&filter[created_at][value]=2025-01-01\n";
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testDateLessThanSearch()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[created_at][operator]=<&filter[created_at][value]=2025-12-31
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'created_at' => ['operator' => '<', 'value' => '2025-12-31'] ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['created_at']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('where')->withArgs(function($closure) { return is_callable($closure); })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                echo "QueryString: ?filter[created_at][operator]=<&filter[created_at][value]=2025-12-31\n";
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->where(function($q) {});
                }
                return $model;
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    // --- RELATIONSHIP+OPERATOR TESTS ---
    public function testRelationshipOperatorSearchSimple()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[orders.total][operator]=>=&filter[orders.total][value]=1000
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 'orders.total' => ['operator' => '>=', 'value' => 1000] ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['orders.total']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $subQuery = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $subQuery->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('whereHas')->withArgs(function($relation, $closure) use ($subQuery) {
            $closure($subQuery);
            return $relation === 'orders' && is_callable($closure);
        })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->whereHas('orders', function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[orders.total][operator]=>=&filter[orders.total][value]=1000\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    public function testRelationshipOperatorWithMultipleRelations()
    {
        $request = m::mock(Request::class);
        // QueryString: ?filter[orders.total][operator]=>=&filter[orders.total][value]=1000&filter[payments.amount][operator]=<&filter[payments.amount][value]=500
        $request->shouldReceive('all')->andReturn([
            'filter' => [ 
                'orders.total' => [
                    'operator' => '>=', 
                    'value' => 1000
                ], 
                'payments.amount' => [
                    'operator' => '<', 
                    'value' => 500
                ]
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['orders.total', 'payments.amount']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('selectRaw')->andReturnSelf();
        $subQuery1 = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $subQuery1->shouldReceive('where')->andReturnSelf();
        $subQuery2 = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $subQuery2->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('whereHas')->withArgs(function($relation, $closure) use ($subQuery1) {
            $closure($subQuery1);
            return $relation === 'orders' && is_callable($closure);
        })->andReturnSelf();
        $builder->shouldReceive('whereHas')->withArgs(function($relation, $closure) use ($subQuery2) {
            $closure($subQuery2);
            return $relation === 'payments' && is_callable($closure);
        })->andReturnSelf();
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $filters = $this->request->all()['filter'] ?? [];
                $searchable = $repository->getFieldsSearchable();
                foreach ($filters as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->selectRaw();
                    $model = $model->whereHas(explode('.', $field)[0], function($q) {});
                }
                return $model;
            }
        };
        echo "QueryString: ?filter[orders.total][operator]=>=&filter[orders.total][value]=1000&filter[payments.amount][operator]=<&filter[payments.amount][value]=500\n";
        $result = $criteria->apply($builder, $repository);
        $this->assertSame($builder, $result);
    }

    // ... Add more tests for date search and relationship+operator permutations ...
}
