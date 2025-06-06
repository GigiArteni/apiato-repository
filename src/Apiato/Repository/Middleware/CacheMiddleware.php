<?php

namespace Apiato\Repository\Middleware;

use Closure;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Cache Middleware - Advanced caching with tags
 */
class CacheMiddleware extends RepositoryMiddleware
{
    protected int $minutes;
    protected array $cacheableMethods;

    public function __construct(int $minutes = 30, array $cacheableMethods = ['all', 'find', 'findWhere', 'paginate'])
    {
        $this->minutes = $minutes;
        $this->cacheableMethods = $cacheableMethods;
    }

    public function handle(RepositoryInterface $repository, string $method, array $args, Closure $next)
    {
        // Clear cache on write operations
        if (in_array($method, ['create', 'update', 'delete', 'bulkInsert', 'bulkUpdate'])) {
            $this->clearRepositoryCache($repository);
            return $next($repository, $method, $args);
        }

        // Cache read operations
        if (in_array($method, $this->cacheableMethods)) {
            $cacheKey = $this->generateCacheKey($repository, $method, $args);
            
            return cache()->tags($this->getCacheTags($repository))
                ->remember($cacheKey, $this->minutes, function() use ($repository, $method, $args, $next) {
                    return $next($repository, $method, $args);
                });
        }

        return $next($repository, $method, $args);
    }

    protected function generateCacheKey($repository, $method, $args): string
    {
        return sprintf('%s:%s:%s', 
            get_class($repository), 
            $method, 
            md5(serialize($args))
        );
    }

    protected function getCacheTags($repository): array
    {
        return [
            'repository',
            get_class($repository),
            $repository->getModel()->getTable(),
        ];
    }

    protected function clearRepositoryCache($repository)
    {
        try {
            cache()->tags($this->getCacheTags($repository))->flush();
        } catch (\Exception $e) {
            // Cache driver doesn't support tags
            logger()->warning('Cache tags not supported', ['driver' => config('cache.default')]);
        }
    }
}