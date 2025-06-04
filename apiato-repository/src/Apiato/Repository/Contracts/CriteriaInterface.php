<?php

namespace Apiato\Repository\Contracts;

/**
 * 100% Compatible with l5-repository CriteriaInterface
 */
interface CriteriaInterface
{
    public function apply($model, RepositoryInterface $repository);
}
