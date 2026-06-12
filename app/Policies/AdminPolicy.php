<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;
use App\Services\RolePermissionService;

class AdminPolicy
{
    use HandlesTenantAuthorization;

    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function listTenants(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function viewTenantUsage(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function viewTenant(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function createTenant(User $user): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'tenants.manage');
    }

    public function updateTenant(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'tenants.manage');
    }

    public function deleteTenant(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'tenants.manage');
    }

    public function reactivateTenant(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'tenants.suspend');
    }

    public function suspendTenant(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'tenants.suspend');
    }

    public function impersonate(User $user): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'users.impersonate');
    }

    public function viewSettings(User $user): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'platform.settings');
    }

    public function updateSettings(User $user): bool
    {
        return $this->viewSettings($user);
    }

    public function viewDashboard(User $user): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'audit.dashboard');
    }

    public function crossTenantMigrate(User $user): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'tenants.manage');
    }
}
