<?php

namespace Apiato\Repository\Middleware;

use Closure;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Performance Monitor Middleware - Track query performance
 */
class PerformanceMonitorMiddleware extends RepositoryMiddleware
{
    protected float $slowQueryThreshold;

    public function __construct(float $slowQueryThreshold = 1000.0) // milliseconds
    {
        $this->slowQueryThreshold = $slowQueryThreshold;
    }

    public function handle(RepositoryInterface $repository, string $method, array $args, Closure $next)
    {
        $startTime = microtime(true);
        $startQueries = $this->getQueryCount();
        
        $result = $next($repository, $method, $args);
        
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $queryCount = $this->getQueryCount() - $startQueries;
        
        // Log performance metrics
        $this->logPerformance($repository, $method, $duration, $queryCount, $args);
        
        // Alert on slow queries
        if ($duration > $this->slowQueryThreshold) {
            $this->alertSlowQuery($repository, $method, $duration, $queryCount);
        }
        
        return $result;
    }

    protected function getQueryCount(): int
    {
        return count(\DB::getQueryLog());
    }

    protected function logPerformance($repository, $method, $duration, $queryCount, $args)
    {
        logger('Repository Performance', [
            'repository' => get_class($repository),
            'method' => $method,
            'duration_ms' => round($duration, 2),
            'query_count' => $queryCount,
            'memory_usage' => memory_get_usage(true),
            'args_size' => strlen(serialize($args)),
        ]);
    }

    protected function alertSlowQuery($repository, $method, $duration, $queryCount)
    {
        logger()->warning('Slow Repository Query', [
            'repository' => get_class($repository),
            'method' => $method,
            'duration_ms' => round($duration, 2),
            'query_count' => $queryCount,
            'threshold_ms' => $this->slowQueryThreshold,
        ]);
    }
}