<?php

namespace Apiato\Repository\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface RepositoryCriteriaInterface
 */
interface RepositoryCriteriaInterface
{
    public function pushCriteria($criteria);
    public function popCriteria($criteria);
    public function getCriteria();
    public function getByCriteria(CriteriaInterface $criteria);
    public function skipCriteria($status = true);
    public function clearCriteria();
    public function applyCriteria();
}
