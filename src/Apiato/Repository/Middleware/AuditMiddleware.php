<?php

namespace Apiato\Repository\Middleware;

use Closure;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Audit Middleware - Tracks all repository operations
 */
class AuditMiddleware extends RepositoryMiddleware
{
    protected array $auditMethods;

    public function __construct(array $auditMethods = ['create', 'update', 'delete'])
    {
        $this->auditMethods = $auditMethods;
    }

    public function handle(RepositoryInterface $repository, string $method, array $args, Closure $next)
    {
        $startTime = microtime(true);
        
        try {
            $result = $next($repository, $method, $args);
            
            if (in_array($method, $this->auditMethods)) {
                $this->logOperation($repository, $method, $args, $result, $startTime);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logError($repository, $method, $args, $e, $startTime);
            throw $e;
        }
    }

    protected function logOperation($repository, $method, $args, $result, $startTime)
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        logger('Repository Operation', [
            'repository' => get_class($repository),
            'method' => $method,
            'args' => $this->sanitizeArgs($args),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'duration_ms' => $duration,
            'timestamp' => now(),
        ]);
    }

    protected function logError($repository, $method, $args, $exception, $startTime)
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        logger()->error('Repository Error', [
            'repository' => get_class($repository),
            'method' => $method,
            'args' => $this->sanitizeArgs($args),
            'error' => $exception->getMessage(),
            'duration_ms' => $duration,
            'timestamp' => now(),
        ]);
    }

    protected function sanitizeArgs($args)
    {
        // Remove sensitive data from logs
        return array_map(function($arg) {
            if (is_array($arg)) {
                return array_diff_key($arg, array_flip(['password', 'token', 'secret']));
            }
            return $arg;
        }, $args);
    }
}