<?php

declare(strict_types=1);

namespace Apiato\Repository\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface CriteriaInterface
{
    public function apply(Builder $model, RepositoryInterface $repository): Builder;
}
