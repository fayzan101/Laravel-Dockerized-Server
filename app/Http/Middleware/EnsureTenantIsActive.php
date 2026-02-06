<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
        public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->tenant) {
            if (!$request->user()->tenant->isActive()) {
                return response()->json([
                    'message' => 'Tenant is not active.',
                ], 403);
            }
        }

        return $next($request);
    }
}
