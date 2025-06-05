<?php

namespace Apiato\Repository\Contracts;

/**
 * Criteria Interface
 * Defines the contract for applying criteria to repository queries
 */
interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository);
}
