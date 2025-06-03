<?php

namespace Apiato\Repository\Traits;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Enhanced caching trait - compatible with l5-repository + performance improvements
 */
trait CacheableRepository
{
    protected ?CacheRepository $cacheRepository = null;
    protected ?int $cacheMinutes = null;
    protected bool $skipCache = false;

    public function setCacheRepository($repository)
    {
        $this->cacheRepository = $repository;
        return $this;
    }

    public function getCacheRepository()
    {
        return $this->cacheRepository ?? Cache::store();
    }

    public function getCacheMinutes()
    {
        return $this->cacheMinutes ?? config('repository.cache.minutes', 30);
    }

    public function skipCache($status = true)
    {
        $this->skipCache = $status;
        return $this;
    }

    public function allowedCache($method)
    {
        $cacheEnabled = config('repository.cache.enabled', false);

        if (!$cacheEnabled) {
            return false;
        }

        $cacheOnly = config('repository.cache.allowed.only');
        $cacheExcept = config('repository.cache.allowed.except');

        if (is_array($cacheOnly)) {
            return in_array($method, $cacheOnly);
        }

        if (is_array($cacheExcept)) {
            return !in_array($method, $cacheExcept);
        }

        if (is_null($cacheOnly) && is_null($cacheExcept)) {
            return true;
        }

        return false;
    }

    public function isSkippedCache()
    {
        $skipped = request()->get(config('repository.cache.params.skipCache', 'skipCache'), false);
        if (is_string($skipped)) {
            $skipped = strtolower($skipped) === 'true';
        }

        return $this->skipCache || $skipped;
    }

    protected function serializeCriteria()
    {
        try {
            return serialize($this->getCriteria());
        } catch (\Exception $e) {
            return serialize([]);
        }
    }

    // Enhanced cache key generation with HashId support
    public function getCacheKey($method, $args = null)
    {
        if (is_null($args)) {
            $args = [];
        }

        $key = sprintf('%s@%s-%s',
            get_called_class(),
            $method,
            serialize($args)
        );

        return $key;
    }
}
