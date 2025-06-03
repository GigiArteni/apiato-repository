<?php

declare(strict_types=1);

namespace Apiato\Repository\Tests\Stubs;

use Apiato\Repository\Eloquent\BaseRepository;

class TestRepository extends BaseRepository
{
    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
        'status' => 'in',
    ];

    public function model(): string
    {
        return TestModel::class;
    }
}
