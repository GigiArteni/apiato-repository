<?php

namespace Apiato\Repository\Traits;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Enhanced caching trait - compatible with l5-repository + performance improvements
 * Includes intelligent cache tagging and invalidation
 */
trait CacheableRepository
{
    protected ?CacheRepository $cacheRepository = null;
    protected ?int $cacheMinutes = null;
    protected bool $skipCache = false;
    protected array $cacheTags = [];

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

    public function getCacheTags()
    {
        if (empty($this->cacheTags) && config('repository.cache.tags.auto_generate', true)) {
            $this->cacheTags = $this->generateCacheTags();
        }
        
        return $this->cacheTags;
    }

    public function flushCache($tags = null)
    {
        $tags = $tags ?? $this->getCacheTags();
        
        if (!empty($tags) && config('repository.cache.tags.enabled', true)) {
            Cache::tags($tags)->flush();
        } else {
            // Fallback to clearing all cache if tags not supported
            Cache::flush();
        }
        
        return $this;
    }

    protected function generateCacheTags()
    {
        $model = $this->model();
        $modelName = class_basename($model);
        
        return [
            strtolower($modelName),
            strtolower($modelName) . 's',
            'repositories'
        ];
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

    // Enhanced cache key generation
    public function getCacheKey($method, $args = null)
    {
        if (is_null($args)) {
            $args = [];
        }

        $key = sprintf('%s@%s-%s-%s',
            get_called_class(),
            $method,
            md5(serialize($args)),
            md5($this->serializeCriteria())
        );

        return $key;
    }

    protected function cacheGet($key, $callback = null)
    {
        if ($this->isSkippedCache() || !$this->allowedCache('get')) {
            return $callback ? $callback() : null;
        }

        $tags = $this->getCacheTags();
        
        if (!empty($tags) && config('repository.cache.tags.enabled', true)) {
            return Cache::tags($tags)->remember($key, $this->getCacheMinutes(), $callback);
        }

        return Cache::remember($key, $this->getCacheMinutes(), $callback);
    }

    protected function clearCacheAfterAction($action)
    {
        if (!config('repository.cache.clean.enabled', true)) {
            return;
        }

        $cleanActions = config('repository.cache.clean.on', []);
        
        if (isset($cleanActions[$action]) && $cleanActions[$action]) {
            $this->flushCache();
        }
    }
}
