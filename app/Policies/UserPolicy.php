<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;
use App\Services\RolePermissionService;

class UserPolicy
{
    use HandlesTenantAuthorization;

    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function viewAny(User $actor, int $tenantId): bool
    {
        return $this->belongsToTenant($actor, $tenantId)
            && $this->permissions->has($actor, 'users.manage');
    }

    public function create(User $actor, int $tenantId): bool
    {
        return $this->belongsToTenant($actor, $tenantId)
            && $this->permissions->has($actor, 'users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        return $this->sameTenant($actor, $target)
            && $this->permissions->has($actor, 'users.manage');
    }

    public function assignRoles(User $actor, User $target): bool
    {
        return $this->sameTenant($actor, $target)
            && $this->permissions->has($actor, 'users.manage');
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->sameTenant($actor, $target)
            && $this->permissions->has($actor, 'users.manage')
            && $actor->id !== $target->id;
    }

    public function removeFromTenant(User $actor, User $target): bool
    {
        return $this->sameTenant($actor, $target)
            && $this->permissions->has($actor, 'users.remove')
            && $actor->id !== $target->id;
    }

    public function viewPermissions(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        return $this->sameTenant($actor, $target)
            && $this->permissions->has($actor, 'users.manage');
    }
}
