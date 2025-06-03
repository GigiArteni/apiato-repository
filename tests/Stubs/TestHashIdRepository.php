<?php

declare(strict_types=1);

namespace Apiato\Repository\Tests\Stubs;

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Traits\HashIdRepository;

class TestHashIdRepository extends BaseRepository
{
    use HashIdRepository;

    protected array $fieldSearchable = [
        'name' => 'like',
        'email' => '=',
    ];

    public function model(): string
    {
        return TestModel::class;
    }
}
