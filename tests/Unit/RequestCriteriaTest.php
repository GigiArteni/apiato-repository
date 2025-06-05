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

    // Add more tests here
}
