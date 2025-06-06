<?php

namespace Apiato\Repository\Middleware;

use Closure;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Rate Limit Middleware - Prevent repository abuse
 */
class RateLimitMiddleware extends RepositoryMiddleware
{
    protected int $maxAttempts;
    protected int $decayMinutes;

    public function __construct(int $maxAttempts = 100, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function handle(RepositoryInterface $repository, string $method, array $args, Closure $next)
    {
        $key = $this->getRateLimitKey($repository, $method);
        
        if (app('cache')->has($key) && app('cache')->get($key) >= $this->maxAttempts) {
            throw new \Exception("Rate limit exceeded for {$method} on " . get_class($repository));
        }
        
        // Increment counter
        $attempts = app('cache')->get($key, 0) + 1;
        app('cache')->put($key, $attempts, now()->addMinutes($this->decayMinutes));
        
        return $next($repository, $method, $args);
    }

    protected function getRateLimitKey($repository, $method): string
    {
        $userId = auth()->id() ?? 'guest';
        $ip = request()->ip();
        
        return sprintf('rate_limit:%s:%s:%s:%s', 
            get_class($repository), 
            $method, 
            $userId, 
            $ip
        );
    }
}