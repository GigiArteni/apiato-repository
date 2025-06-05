<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Apiato\Repository\Eloquent\BaseRepository;

class BaseRepositoryTest extends TestCase
{
    protected $repository;
    protected $model;
    protected $app;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->app = m::mock(Container::class);
        $this->model = m::mock(Model::class);
        $this->repository = new TestRepository($this->app);
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testRepositoryCanBeInstantiated()
    {
        $this->assertInstanceOf(BaseRepository::class, $this->repository);
    }

    public function testModelMethodReturnsCorrectClass()
    {
        $this->assertEquals(TestModel::class, $this->repository->model());
    }

    // Add more tests here
}

// Test doubles
class TestRepository extends BaseRepository
{
    public function model()
    {
        return TestModel::class;
    }
}

class TestModel extends Model
{
    protected $fillable = ['name', 'email'];
}
