<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;

class AdminController extends Controller
{
    // GET /admin/tenants
    public function listTenants(Request $request)
    {
        // Example: Only allow super-admin (customize as needed)
        $user = $request->user();
        if (!$user || $user->role !== 'super-admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(Tenant::all());
    }

    // GET /admin/tenants/{tenantId}/usage
    public function tenantUsage(Request $request, $tenantId)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'super-admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }
        // Example: Return dummy usage data (customize as needed)
        return response()->json([
            'tenant_id' => $tenantId,
            'usage' => [
                'users' => $tenant->users()->count(),
                'projects' => 0 // Replace with real data
            ]
        ]);
    }

    // POST /admin/tenants/{tenantId}/suspend
    public function suspendTenant(Request $request, $tenantId)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'super-admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }
        $tenant->update(['status' => 'suspended']);
        return response()->json(['message' => 'Tenant suspended']);
    }

    // POST /admin/impersonate-user
    public function impersonateUser(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'super-admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate(['user_id' => 'required|exists:users,id']);
        $target = User::find($request->user_id);
        // Example: Return a token or info for impersonation (customize as needed)
        return response()->json(['message' => 'Impersonation started', 'user' => $target]);
    }
}
