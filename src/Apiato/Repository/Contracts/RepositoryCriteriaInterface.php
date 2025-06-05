<?php

namespace Apiato\Repository\Contracts;

use Illuminate\Support\Collection;

/**
 * Repository Criteria Interface
 * Defines the contract for managing criteria in repositories
 */
interface RepositoryCriteriaInterface
{
    /**
     * Push Criteria for filter the query
     *
     * @param $criteria
     * @return $this
     */
    public function pushCriteria($criteria);

    /**
     * Pop Criteria
     *
     * @param $criteria
     * @return $this
     */
    public function popCriteria($criteria);

    /**
     * Get Collection of Criteria
     *
     * @return Collection
     */
    public function getCriteria();

    /**
     * Find data by Criteria
     *
     * @param CriteriaInterface $criteria
     * @return mixed
     */
    public function getByCriteria(CriteriaInterface $criteria);

    /**
     * Skip Criteria
     *
     * @param bool $status
     * @return $this
     */
    public function skipCriteria($status = true);

    /**
     * Clear all Criteria
     *
     * @return $this
     */
    public function clearCriteria();

    /**
     * Apply criteria in current Query
     *
     * @return $this
     */
    public function applyCriteria();
}
