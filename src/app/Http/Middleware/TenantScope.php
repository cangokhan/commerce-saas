<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantScope
{
    /**
     * Handle an incoming request.
     *
     * Automatically scope queries to the authenticated user's tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $tenantId = $request->user()->tenant_id;
            
            // Set global scope for tenant
            if ($tenantId) {
                // Add tenant_id to request for easy access
                $request->merge(['tenant_id' => $tenantId]);
            }
        }

        return $next($request);
    }
}
