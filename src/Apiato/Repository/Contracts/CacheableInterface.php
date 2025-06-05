<?php

namespace Apiato\Repository\Contracts;

/**
 * Cacheable Interface
 * Defines the contract for caching functionality
 */
interface CacheableInterface
{
    /**
     * Set Cache Repository
     *
     * @param mixed $repository
     * @return $this
     */
    public function setCacheRepository($repository);

    /**
     * Get Cache Repository
     *
     * @return mixed
     */
    public function getCacheRepository();

    /**
     * Get Cache Key
     *
     * @param string $method
     * @param mixed $args
     * @return string
     */
    public function getCacheKey($method, $args = null);

    /**
     * Get Cache Minutes
     *
     * @return int
     */
    public function getCacheMinutes();

    /**
     * Skip Cache
     *
     * @param bool $status
     * @return $this
     */
    public function skipCache($status = true);
}
