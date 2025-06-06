<?php

namespace Apiato\Repository\Middleware;

use Closure;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Repository Middleware System
 * Provides Laravel-style middleware for repositories
 */
abstract class RepositoryMiddleware
{
    /**
     * Handle repository operation
     *
     * @param RepositoryInterface $repository
     * @param string $method
     * @param array $args
     * @param Closure $next
     * @return mixed
     */
    abstract public function handle(RepositoryInterface $repository, string $method, array $args, Closure $next);
}