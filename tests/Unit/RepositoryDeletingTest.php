<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Apiato\Repository\Events\RepositoryDeleting;
use Apiato\Repository\Contracts\RepositoryInterface;

class RepositoryDeletingTest extends TestCase
{
    public function testActionIsDeleting()
    {
        $repo = \Mockery::mock(RepositoryInterface::class);
        $event = new RepositoryDeleting($repo, '123');
        $this->assertSame('deleting', $event->getAction());
    }

    public function testGetModelIdReturnsModelId()
    {
        $repo = \Mockery::mock(RepositoryInterface::class);
        $event = new RepositoryDeleting($repo, '456');
        $this->assertSame('456', $event->getModelId());
    }

    public function testGetModelIdWithNull()
    {
        $repo = \Mockery::mock(RepositoryInterface::class);
        $event = new RepositoryDeleting($repo, null);
        $this->assertNull($event->getModelId());
    }

    public function testGetModelIdWithObject()
    {
        $repo = \Mockery::mock(RepositoryInterface::class);
        $model = (object)['id' => 789];
        $event = new RepositoryDeleting($repo, $model);
        $this->assertSame($model, $event->getModelId());
    }

    public function testGetModelAndRepository()
    {
        $repo = \Mockery::mock(RepositoryInterface::class);
        $model = (object)['id' => 1];
        $event = new RepositoryDeleting($repo, $model);
        $this->assertSame($model, $event->getModel());
        $this->assertSame($repo, $event->getRepository());
    }

    public function testToArray()
    {
        $repo = \Mockery::mock(RepositoryInterface::class);
        $model = (object)['id' => 2];
        $event = new RepositoryDeleting($repo, $model);
        $arr = $event->toArray();
        $this->assertSame('deleting', $arr['action']);
        $this->assertSame(get_class($repo), $arr['repository_class']);
        $this->assertArrayHasKey('timestamp', $arr);
    }
}
