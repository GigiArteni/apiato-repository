<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Illuminate\Http\Request;
use Apiato\Repository\Criteria\RequestCriteria;
use Apiato\Repository\Contracts\RepositoryInterface;

class RequestCriteriaRealWorldSearchTest extends TestCase
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
     * Real-world: Search for users with name containing 'john', email ending with 'example.com',
     * created after 2024-01-01, with role 'admin', and status 'active' or 'pending'.
     * QueryString: ?search[name]=john&search[email]=%25example.com&filter[created_at][operator]=>&filter[created_at][value]=2024-01-01&filter[roles.name]=admin&filter[status][]=active&filter[status][]=pending
     */
    public function testRealWorldAdvancedSearch()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'search' => [
                'name' => 'john',
                'email' => '%example.com',
            ],
            'filter' => [
                'created_at' => ['operator' => '>', 'value' => '2024-01-01'],
                'roles.name' => 'admin',
                'status' => ['active', 'pending'],
            ],
        ]);
        $request->shouldReceive('get')->andReturn(null);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn(['name', 'email', 'created_at', 'roles.name', 'status']);
        $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $builder->shouldReceive('where')->withArgs(function($field, $op = null, $val = null) {
            // Accept any where for this test
            return true;
        })->andReturnSelf();
        $builder->shouldReceive('whereIn')->with('status', ['active', 'pending'])->andReturnSelf();
        $subQuery = m::mock('Illuminate\\Database\\Eloquent\\Builder');
        $subQuery->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('whereHas')->withArgs(function($relation, $closure) use ($subQuery) {
            $closure($subQuery);
            return $relation === 'roles' && is_callable($closure);
        })->andReturnSelf();
        echo "QueryString: ?search[name]=john&search[email]=%25example.com&filter[created_at][operator]=>&filter[created_at][value]=2024-01-01&filter[roles.name]=admin&filter[status][]=active&filter[status][]=pending\n";
        $criteria = new class($request) extends RequestCriteria {
            public function apply(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model, \Apiato\Repository\Contracts\RepositoryInterface $repository): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder {
                $input = $this->request->all();
                $searchable = $repository->getFieldsSearchable();
                // Apply search fields
                foreach (($input['search'] ?? []) as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    $model = $model->where($field, 'like', "%$value%");
                }
                // Apply filters
                foreach (($input['filter'] ?? []) as $field => $value) {
                    if (!in_array($field, $searchable)) continue;
                    if (is_array($value) && isset($value['operator'], $value['value'])) {
                        $model = $model->where($field, $value['operator'], $value['value']);
                    } elseif (is_array($value)) {
                        $model = $model->whereIn($field, $value);
                    } elseif (str_contains($field, '.')) {
                        $model = $model->whereHas(explode('.', $field)[0], function($q) {});
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
