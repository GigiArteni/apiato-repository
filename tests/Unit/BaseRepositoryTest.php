<?php

declare(strict_types=1);

namespace Apiato\Repository\Tests\Unit;

use Apiato\Repository\Tests\TestCase;
use Apiato\Repository\Tests\Stubs\TestRepository;
use Apiato\Repository\Tests\Stubs\TestModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(TestRepository::class);
    }

    public function test_can_create_model(): void
    {
        $data = ['name' => 'Test Name', 'email' => 'test@example.com'];
        $model = $this->repository->create($data);

        $this->assertInstanceOf(TestModel::class, $model);
        $this->assertEquals($data['name'], $model->name);
        $this->assertEquals($data['email'], $model->email);
    }

    public function test_can_find_model(): void
    {
        $model = TestModel::factory()->create();
        $found = $this->repository->find($model->id);

        $this->assertInstanceOf(TestModel::class, $found);
        $this->assertEquals($model->id, $found->id);
    }

    public function test_can_update_model(): void
    {
        $model = TestModel::factory()->create();
        $newData = ['name' => 'Updated Name'];
        
        $updated = $this->repository->update($newData, $model->id);

        $this->assertEquals($newData['name'], $updated->name);
    }

    public function test_can_delete_model(): void
    {
        $model = TestModel::factory()->create();
        $result = $this->repository->delete($model->id);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('test_models', ['id' => $model->id]);
    }

    public function test_can_paginate_results(): void
    {
        TestModel::factory()->count(20)->create();
        
        $results = $this->repository->paginate(10);

        $this->assertEquals(10, $results->count());
        $this->assertEquals(20, $results->total());
    }
}
