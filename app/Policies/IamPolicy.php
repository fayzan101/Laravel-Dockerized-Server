<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;
use App\Services\RolePermissionService;

class IamPolicy
{
    use HandlesTenantAuthorization;

    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function manage(User $user): bool
    {
        return $user->tenant_id !== null && $this->permissions->has($user, 'roles.view');
    }

    public function viewAnyRole(User $user): bool
    {
        return $this->manage($user);
    }

    public function createRole(User $user): bool
    {
        return $this->isTenantAdmin($user) && $this->permissions->has($user, 'roles.manage');
    }

    public function updateRole(User $user, Role $role): bool
    {
        return $this->isTenantAdmin($user)
            && $user->tenant_id === $role->tenant_id
            && $this->permissions->has($user, 'roles.manage');
    }

    public function deleteRole(User $user, Role $role): bool
    {
        return $this->updateRole($user, $role);
    }

    public function viewAnyPermission(User $user): bool
    {
        return $this->manage($user);
    }

    public function createPermission(User $user): bool
    {
        return $this->isTenantAdmin($user) && $this->permissions->has($user, 'roles.manage');
    }

    public function updatePermission(User $user, Permission $permission): bool
    {
        return $this->isTenantAdmin($user)
            && $user->tenant_id === $permission->tenant_id
            && $this->permissions->has($user, 'roles.manage');
    }

    public function deletePermission(User $user, Permission $permission): bool
    {
        return $this->updatePermission($user, $permission);
    }

    public function assignRoles(User $user, User $target): bool
    {
        return $this->isTenantAdmin($user)
            && $this->sameTenant($user, $target)
            && $this->permissions->has($user, 'users.manage');
    }

    public function viewPermissions(User $user, User $target): bool
    {
        return $this->sameTenant($user, $target)
            && ($this->permissions->has($user, 'users.manage') || $user->id === $target->id);
    }
}
