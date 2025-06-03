<?php

declare(strict_types=1);

namespace Apiato\Repository\Traits;

use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin \Apiato\Repository\Eloquent\BaseRepository
 */
trait CacheableRepository
{
    protected ?int $cacheMinutes = null;
    protected ?string $cacheKey = null;
    protected bool $skipCache = false;
    protected array $cacheOnly = [];
    protected array $cacheExcept = [];
    protected array $cacheTags = [];

    public function cacheMinutes(int $minutes): static
    {
        $this->cacheMinutes = $minutes;
        return $this;
    }

    public function cacheKey(string $key): static
    {
        $this->cacheKey = $key;
        return $this;
    }

    public function skipCache(bool $status = true): static
    {
        $this->skipCache = $status;
        return $this;
    }

    public function clearCache(): bool
    {
        $cache = $this->getCacheRepository();

        if (method_exists($cache, 'tags') && !empty($this->getCacheTags())) {
            return $cache->tags($this->getCacheTags())->flush();
        }

        return $cache->flush();
    }

    public function getCacheKey(string $method, array $args = []): string
    {
        if (isset($this->cacheKey)) {
            return $this->cacheKey;
        }

        $modelName = str_replace('\\', '.', strtolower(get_class($this->model)));
        $argsKey = !empty($args) ? md5(serialize($args)) : '';
        $criteriaKey = $this->criteria->isNotEmpty() ? md5(serialize($this->criteria->toArray())) : '';

        return sprintf('repository.%s.%s.%s.%s', $modelName, $method, $argsKey, $criteriaKey);
    }

    public function getCacheMinutes(): int
    {
        return $this->cacheMinutes ?? config('repository.cache.minutes', 60);
    }

    public function getCacheTags(): array
    {
        if (!empty($this->cacheTags)) {
            return $this->cacheTags;
        }

        return [str_replace('\\', '.', strtolower(get_class($this->model)))];
    }

    protected function getCacheRepository(): CacheRepository|TaggedCache
    {
        $cache = Cache::store(config('repository.cache.store'));

        if (method_exists($cache, 'tags') && !empty($this->getCacheTags())) {
            return $cache->tags($this->getCacheTags());
        }

        return $cache;
    }

    protected function shouldCache(string $method): bool
    {
        if ($this->skipCache || !config('repository.cache.enabled', true)) {
            return false;
        }

        if (!empty($this->cacheOnly)) {
            return in_array($method, $this->cacheOnly);
        }

        if (!empty($this->cacheExcept)) {
            return !in_array($method, $this->cacheExcept);
        }

        return in_array($method, config('repository.cache.allowed_methods', []));
    }

    protected function cacheResult(string $method, array $args, callable $callback): mixed
    {
        if (!$this->shouldCache($method)) {
            return $callback();
        }

        $key = $this->getCacheKey($method, $args);
        $cache = $this->getCacheRepository();

        return $cache->remember($key, $this->getCacheMinutes(), $callback);
    }

    protected function clearCacheAfterWrite(): void
    {
        if (config('repository.cache.clear_on_write', true)) {
            $this->clearCache();
        }
    }
}
