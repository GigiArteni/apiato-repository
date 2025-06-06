<?php

namespace Apiato\Repository\Middleware;

use Closure;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Tenant Scope Middleware - Multi-tenant filtering
 */
class TenantScopeMiddleware extends RepositoryMiddleware
{
    protected string $tenantField;
    protected $tenantValue;

    public function __construct(string $tenantField = 'company_id', $tenantValue = null)
    {
        $this->tenantField = $tenantField;
        $this->tenantValue = $tenantValue ?? $this->getCurrentTenant();
    }

    public function handle(RepositoryInterface $repository, string $method, array $args, Closure $next)
    {
        // Apply tenant scope to read operations
        if (in_array($method, ['all', 'find', 'findWhere', 'paginate'])) {
            $repository->scopeQuery(function($query) {
                return $query->where($this->tenantField, $this->tenantValue);
            });
        }
        
        // Add tenant field to create operations
        if ($method === 'create' && isset($args[0]) && is_array($args[0])) {
            $args[0][$this->tenantField] = $this->tenantValue;
        }
        
        return $next($repository, $method, $args);
    }

    protected function getCurrentTenant()
    {
        // Get tenant from authenticated user, session, or request
        if (auth()->check() && method_exists(auth()->user(), 'getCurrentTenant')) {
            return auth()->user()->getCurrentTenant();
        }
        
        return session('current_tenant_id') ?? request()->header('X-Tenant-ID');
    }
}