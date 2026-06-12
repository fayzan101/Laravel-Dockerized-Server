<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
        public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user && $user->tenant && ! $user->tenant->isActive()) {
            return response()->json([
                'message' => 'Tenant is not active.',
            ], 403);
        }

        return $next($request);
    }
}
