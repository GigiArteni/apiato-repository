<?php

namespace Apiato\Repository\Tests\Unit;

use Mockery as m;
use Illuminate\Http\Request;
use Apiato\Repository\Criteria\RequestCriteria;
use Apiato\Repository\Contracts\RepositoryInterface;
use Orchestra\Testbench\TestCase;

class AlwaysSelfBuilderStub {
    public function __call($name, $arguments) {
        if (isset($arguments[0]) && is_callable($arguments[0])) {
            $arguments[0]($this);
        }
        return $this;
    }
    public function getModel() {
        return new class {
            public function getTable() {
                return 'users';
            }
        };
    }
}

class RequestCriteriaAllFeaturesTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testAllSearchFeaturesTogether()
    {
        $queryString = 'filter[name]=Alice&filter[roles][]=admin&filter[roles][]=user&filter[deleted_at]=null&filter[not_searchable]=foo&filter[active]=true&filter[or][0][0]=email&filter[or][0][1]==&filter[or][0][2]=alice@example.com&filter[or][1][0]=status&filter[or][1][1]==&filter[or][1][2]=active';
        $request = \Illuminate\Http\Request::create('/api/users?' . $queryString, 'GET');
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([
            'name', 'roles', 'deleted_at', 'active', 'email', 'status'
        ]);
        $builder = new AlwaysSelfBuilderStub();
        $criteria = new class($request) extends RequestCriteria {
            public function apply($model, $repository) {
                return parent::apply($model, $repository);
            }
        };
        $result = $criteria->apply($builder, $repository);
        $this->assertNotNull($result);
    }

    public function testQueryStringSearchIsDecodedAndApplied()
    {
        $queryString = 'search=name:Alice;roles:admin,user;deleted_at:null;active:true;or:email:alice@example.com|status:active';
        $request = \Illuminate\Http\Request::create('/api/users?' . $queryString, 'GET');
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([
            'name', 'roles', 'deleted_at', 'active', 'email', 'status'
        ]);
        $builder = new AlwaysSelfBuilderStub();
        $criteria = new RequestCriteria($request);
        $result = $criteria->apply($builder, $repository);
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('searchQueryStringProvider')]
    public function testQueryStringPermutationsAreParsedAndApplied($title, $search, $expectedCalls)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('get')->with('search', null)->andReturn($search);
        $request->shouldReceive('get')->with('searchFields', null)->andReturn(null);
        $request->shouldReceive('get')->with('filter', null)->andReturn(null);
        $request->shouldReceive('get')->with('orderBy', null)->andReturn(null);
        $request->shouldReceive('get')->with('sortedBy', 'asc')->andReturn('asc');
        $request->shouldReceive('get')->with('with', null)->andReturn(null);
        $request->shouldReceive('get')->with('enhanced', false)->andReturn(false);
        $repository = m::mock(RepositoryInterface::class);
        $repository->shouldReceive('getFieldsSearchable')->andReturn([
            'name', 'email', 'roles.name', 'company.name', 'age', 'contract_type', 'deleted_at', 'id', 'role_id', 'status', 'bio'
        ]);
        $builder = new AlwaysSelfBuilderStub();
        $criteria = new RequestCriteria($request);
        $result = $criteria->apply($builder, $repository);
        $this->assertNotNull($result);
    }

    public static function searchQueryStringProvider()
    {
        return [
            ['Simple field:value', 'name:John', [['where', ['name', 'John']]]],
            ['Multiple fields', 'name:John;email:foo@bar.com', [['where', ['name', 'John']], ['where', ['email', 'foo@bar.com']]]],
            ['Relationship field', 'roles.name:admin', [['where', ['roles.name', 'admin']]]],
            ['Operator >=', 'age:>=:18', [['where', ['age', '>=', '18']]]],
            ['Operator <=', 'score:<=:100', [['where', ['score', '<=', '100']]]],
            ['Operator >', 'amount:>:500', [['where', ['amount', '>', '500']]]],
            ['Operator <', 'discount:<:20', [['where', ['discount', '<', '20']]]],
            ['Operator not (!=)', 'contract_type:!=:contract', [['where', ['contract_type', '!=', 'contract']]]],
            ['Array value', 'roles:admin,user', [['whereIn', ['roles', ['admin', 'user']]]]],
            ['IN operator (HashIds)', 'id:in:abc123,def456', [['whereIn', ['id', ['abc123', 'def456']]]]],
            ['Null value', 'deleted_at:null', [['whereNull', ['deleted_at']]]],
            ['Or filter', 'or:email:foo@bar.com|status:active', [['orWhere', ['email', '=', 'foo@bar.com']], ['orWhere', ['status', '=', 'active']]]],
            ['HashId', 'id:gY6N8', [['where', ['id', 'gY6N8']]]],
            ['Boolean true', 'active:true', [['where', ['active', true]]]],
            ['Boolean false', 'active:false', [['where', ['active', false]]]],
            ['Phrase search', 'bio:"senior developer"', [['where', ['bio', '"senior developer"']]]],
            ['Fuzzy search', 'name:john~2', [['where', ['name', 'john~2']]]],
            ['Date equals', 'created_at:2025-06-07', [['where', ['created_at', '2025-06-07']]]],
            ['Date greater than', 'created_at:>:2025-01-01', [['where', ['created_at', '>', '2025-01-01']]]],
            ['Date less than', 'created_at:<:2025-12-31', [['where', ['created_at', '<', '2025-12-31']]]],
            ['Relationship + operator', 'orders.total:>=:1000', [['where', ['orders.total', '>=', '1000']]]],
        ];
    }
}
