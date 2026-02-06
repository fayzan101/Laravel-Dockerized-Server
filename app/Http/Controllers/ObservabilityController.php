<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObservabilityController extends Controller
{
        public function health()
    {
        return response()->json([
            'status' => 'ok',
        ]);
    }

        public function status()
    {
        $dbStatus = 'unknown';

        try {
            DB::connection()->getPdo();
            $dbStatus = 'ok';
        } catch (\Throwable $exception) {
            $dbStatus = 'error';
        }

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'database' => $dbStatus,
        ]);
    }

        public function metrics()
    {
        return response()->json([
            'tenants' => [
                'total' => Tenant::count(),
                'active' => Tenant::where('status', 'active')->count(),
                'inactive' => Tenant::where('status', 'inactive')->count(),
                'suspended' => Tenant::where('status', 'suspended')->count(),
            ],
            'users' => [
                'total' => User::count(),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

        public function tenantMetrics(Request $request, int $tenantId)
    {
        $user = $request->user();

        if (!$user || $user->tenant_id !== $tenantId || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
            ],
            'users' => [
                'total' => User::where('tenant_id', $tenant->id)->count(),
                'admins' => User::where('tenant_id', $tenant->id)->where('role', 'admin')->count(),
                'members' => User::where('tenant_id', $tenant->id)->where('role', 'member')->count(),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
