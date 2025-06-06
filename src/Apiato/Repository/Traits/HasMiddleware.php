<?php

namespace Apiato\Repository\Traits;

use Closure;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Repository Middleware Manager
 */
trait HasMiddleware
{
    protected array $middleware = [];
    protected array $middlewareInstances = [];

    /**
     * Execute method through middleware stack
     */
    protected function executeWithMiddleware(string $method, array $args, Closure $callback)
    {
        if (empty($this->middleware)) {
            return $callback();
        }

        $pipeline = array_reduce(
            array_reverse($this->getMiddlewareInstances()),
            function ($carry, $middleware) use ($method, $args) {
                return function ($repository) use ($carry, $middleware, $method, $args) {
                    return $middleware->handle($repository, $method, $args, $carry);
                };
            },
            function () use ($callback) {
                return $callback();
            }
        );

        return $pipeline($this);
    }

    /**
     * Get middleware instances
     */
    protected function getMiddlewareInstances(): array
    {
        if (empty($this->middlewareInstances)) {
            $this->middlewareInstances = array_map(function ($middleware) {
                return $this->resolveMiddleware($middleware);
            }, $this->middleware);
        }

        return $this->middlewareInstances;
    }

    /**
     * Resolve middleware from string or instance
     */
    protected function resolveMiddleware($middleware): RepositoryMiddleware
    {
        if ($middleware instanceof RepositoryMiddleware) {
            return $middleware;
        }

        if (is_string($middleware)) {
            [$class, $params] = $this->parseMiddleware($middleware);
            return new $class(...$params);
        }

        throw new \InvalidArgumentException('Invalid middleware: ' . gettype($middleware));
    }

    /**
     * Parse middleware string with parameters
     */
    protected function parseMiddleware(string $middleware): array
    {
        [$name, $params] = array_pad(explode(':', $middleware, 2), 2, '');
        
        $class = match($name) {
            'audit' => AuditMiddleware::class,
            'cache' => CacheMiddleware::class,
            'rate-limit' => RateLimitMiddleware::class,
            'tenant-scope' => TenantScopeMiddleware::class,
            'performance' => PerformanceMonitorMiddleware::class,
            default => $name, // Assume it's a full class name
        };

        $parsedParams = [];
        if ($params) {
            $parsedParams = explode(',', $params);
            // Convert numeric strings to numbers
            $parsedParams = array_map(function($param) {
                return is_numeric($param) ? (int)$param : $param;
            }, $parsedParams);
        }

        return [$class, $parsedParams];
    }

    /**
     * Add middleware to the stack
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        // Clear cached instances
        $this->middlewareInstances = [];

        return $this;
    }

    /**
     * Override repository methods to use middleware
     */
    public function all($columns = ['*'])
    {
        return $this->executeWithMiddleware('all', [$columns], function() use ($columns) {
            return parent::all($columns);
        });
    }

    public function find($id, $columns = ['*'])
    {
        return $this->executeWithMiddleware('find', [$id, $columns], function() use ($id, $columns) {
            return parent::find($id, $columns);
        });
    }

    public function create(array $attributes)
    {
        return $this->executeWithMiddleware('create', [$attributes], function() use ($attributes) {
            return parent::create($attributes);
        });
    }

    public function update(array $attributes, $id)
    {
        return $this->executeWithMiddleware('update', [$attributes, $id], function() use ($attributes, $id) {
            return parent::update($attributes, $id);
        });
    }

    public function delete($id)
    {
        return $this->executeWithMiddleware('delete', [$id], function() use ($id) {
            return parent::delete($id);
        });
    }
}