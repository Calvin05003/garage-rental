<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canRent(), 403, 'Only approved tenants can access this page.');

        return $next($request);
    }
}
