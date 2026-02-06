<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class IamController extends Controller
{
        public function createRole(Request $request)
    {
        $actor = $request->user();

        if (!$actor || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $role = Role::create([
            'tenant_id' => $actor->tenant_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role,
        ], 201);
    }

        public function listRoles(Request $request)
    {
        $actor = $request->user();

        if (!$actor) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $roles = Role::where('tenant_id', $actor->tenant_id)->get();

        return response()->json($roles);
    }

        public function createPermission(Request $request)
    {
        $actor = $request->user();

        if (!$actor || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create([
            'tenant_id' => $actor->tenant_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission,
        ], 201);
    }

        public function getUserPermissions(Request $request, int $userId)
    {
        $actor = $request->user();

        if (!$actor || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $target = User::find($userId);

        if (!$target) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($target->tenant_id !== $actor->tenant_id) {
            return response()->json(['message' => 'User is not in this tenant'], 403);
        }

        $roles = $target->roles()->with('permissions')->get();
        $permissions = $roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id')->values();

        return response()->json([
            'user_id' => $target->id,
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            }),
        ]);
    }
}
