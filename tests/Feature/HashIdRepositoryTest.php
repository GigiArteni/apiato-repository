<?php

declare(strict_types=1);

namespace Apiato\Repository\Tests\Feature;

use Apiato\Repository\Tests\TestCase;
use Apiato\Repository\Tests\Stubs\TestHashIdRepository;
use Apiato\Repository\Tests\Stubs\TestModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HashIdRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TestHashIdRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(TestHashIdRepository::class);
    }

    public function test_can_find_by_hash_id(): void
    {
        $model = TestModel::factory()->create();
        $hashId = $this->repository->encodeHashId($model->id);
        
        $found = $this->repository->findByHashId($hashId);

        $this->assertInstanceOf(TestModel::class, $found);
        $this->assertEquals($model->id, $found->id);
    }

    public function test_can_update_by_hash_id(): void
    {
        $model = TestModel::factory()->create();
        $hashId = $this->repository->encodeHashId($model->id);
        $newData = ['name' => 'Updated Name'];
        
        $updated = $this->repository->updateByHashId($newData, $hashId);

        $this->assertEquals($newData['name'], $updated->name);
    }

    public function test_can_delete_by_hash_id(): void
    {
        $model = TestModel::factory()->create();
        $hashId = $this->repository->encodeHashId($model->id);
        
        $result = $this->repository->deleteByHashId($hashId);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('test_models', ['id' => $model->id]);
    }
}
