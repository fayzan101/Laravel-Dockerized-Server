<?php

namespace App\Http\Controllers;

use App\Http\Requests\Iam\AssignUserRolesRequest;
use App\Http\Requests\Iam\CreatePermissionRequest;
use App\Http\Requests\Iam\CreateRoleRequest;
use App\Http\Requests\Iam\SyncRolePermissionsRequest;
use App\Http\Requests\Iam\UpdatePermissionRequest;
use App\Http\Requests\Iam\UpdateRoleRequest;
use App\Http\Requests\Iam\UpdateSystemRoleRequest;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;

class IamController extends Controller
{
    public function __construct(
        private RolePermissionService $permissions,
        private AuditLogger $auditLogger
    ) {
    }

    public function listRoles(Request $request)
    {
        $this->authorize('viewAnyRole', Role::class);

        $systemRoles = collect([
            ['type' => 'system', 'name' => UserRole::Admin->value, 'description' => 'Tenant administrator'],
            ['type' => 'system', 'name' => UserRole::Member->value, 'description' => 'Tenant member'],
        ]);

        $customRoles = Role::query()
            ->withCount('permissions', 'users')
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'system_roles' => $systemRoles,
            'custom_roles' => $customRoles,
        ]);
    }

    public function createRole(CreateRoleRequest $request)
    {
        $this->authorize('createRole', Role::class);

        $role = Role::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->filled('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        $this->auditLogger->activity('role.created', $request->user(), $role);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions'),
        ], 201);
    }

    public function updateRole(UpdateRoleRequest $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);
        $this->authorize('updateRole', $role);

        $role->update($request->only(['name', 'description']));

        $this->auditLogger->activity('role.updated', $request->user(), $role);

        return response()->json(['message' => 'Role updated', 'role' => $role->fresh()]);
    }

    public function deleteRole(Request $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);
        $this->authorize('deleteRole', $role);

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        $this->auditLogger->activity('role.deleted', $request->user(), $role);

        return response()->json(['message' => 'Role deleted']);
    }

    public function listPermissions(Request $request)
    {
        $this->authorize('viewAnyPermission', Permission::class);

        return response()->json(
            Permission::query()->latest()->paginate($this->perPage($request))
        );
    }

    public function createPermission(CreatePermissionRequest $request)
    {
        $this->authorize('createPermission', Permission::class);

        $permission = Permission::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $this->auditLogger->activity('permission.created', $request->user(), $permission);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission,
        ], 201);
    }

    public function updatePermission(UpdatePermissionRequest $request, int $permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $this->authorize('updatePermission', $permission);

        $permission->update($request->only(['name', 'description']));

        $this->auditLogger->activity('permission.updated', $request->user(), $permission);

        return response()->json(['message' => 'Permission updated', 'permission' => $permission->fresh()]);
    }

    public function deletePermission(Request $request, int $permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $this->authorize('deletePermission', $permission);

        $permission->roles()->detach();
        $permission->delete();

        $this->auditLogger->activity('permission.deleted', $request->user(), $permission);

        return response()->json(['message' => 'Permission deleted']);
    }

    public function syncRolePermissions(SyncRolePermissionsRequest $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);
        $this->authorize('updateRole', $role);

        $role->permissions()->sync($request->permission_ids);

        $this->auditLogger->activity('role.permissions_synced', $request->user(), $role);

        return response()->json([
            'message' => 'Role permissions updated',
            'role' => $role->load('permissions'),
        ]);
    }

    public function assignUserRoles(AssignUserRolesRequest $request, int $userId)
    {
        $target = User::findOrFail($userId);
        $this->authorize('assignRoles', $target);

        $roleIds = Role::whereIn('id', $request->role_ids)
            ->where('tenant_id', $target->tenant_id)
            ->pluck('id');

        $target->roles()->sync($roleIds);

        $this->auditLogger->activity('user.roles_assigned', $request->user(), $target, [
            'role_ids' => $roleIds->all(),
        ]);

        return response()->json([
            'message' => 'User roles updated',
            'user_id' => $target->id,
            'roles' => $target->roles()->get(['roles.id', 'roles.name']),
        ]);
    }

    public function updateSystemRole(UpdateSystemRoleRequest $request, int $userId)
    {
        $target = User::findOrFail($userId);
        $this->authorize('update', $target);

        $target->update(['role' => $request->role]);

        $this->auditLogger->activity('user.system_role_updated', $request->user(), $target, [
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'System role updated',
            'user_id' => $target->id,
            'system_role' => $target->role,
        ]);
    }

    public function getUserPermissions(Request $request, int $userId)
    {
        $target = User::findOrFail($userId);
        $this->authorize('viewPermissions', $target);

        $permissionNames = $this->permissions->forUser($target);

        return response()->json([
            'user_id' => $target->id,
            'system_role' => $target->role,
            'custom_roles' => $target->roles()->get(['roles.id', 'roles.name', 'roles.description']),
            'permissions' => collect($permissionNames)->map(fn (string $name) => [
                'name' => $name,
            ])->values(),
        ]);
    }
}
