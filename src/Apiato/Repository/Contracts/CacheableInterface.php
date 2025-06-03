<?php

namespace Apiato\Repository\Contracts;

/**
 * 100% Compatible with l5-repository CacheableInterface
 */
interface CacheableInterface
{
    public function setCacheRepository($repository);
    public function getCacheRepository();
    public function getCacheKey($method, $args = null);
    public function getCacheMinutes();
    public function skipCache($status = true);
}
