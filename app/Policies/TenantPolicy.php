<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;
use App\Services\RolePermissionService;

class TenantPolicy
{
    use HandlesTenantAuthorization;

    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user) || $this->belongsToTenant($user, $tenant->id);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'tenant.update');
    }

    public function manageUsers(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'users.manage');
    }

    public function inviteUsers(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'users.invite');
    }

    public function removeUsers(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'users.remove');
    }

    public function viewMetrics(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id) && $this->isTenantAdmin($user);
    }

    public function viewFeatures(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id);
    }

    public function overrideFeatures(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'features.override');
    }

    public function deleteData(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'data.delete');
    }

    public function viewAuditLogs(User $user, Tenant $tenant): bool
    {
        return $this->isSuperAdmin($user) || $this->belongsToTenant($user, $tenant->id);
    }

    public function manageIntegrations(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'integrations.manage');
    }

    public function transferOwnership(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $tenant->owner_id === $user->id
            && $this->permissions->has($user, 'tenant.update');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return ($this->isSuperAdmin($user) && $this->permissions->has($user, 'tenants.manage'))
            || ($this->belongsToTenant($user, $tenant->id)
                && $tenant->owner_id === $user->id
                && $this->permissions->has($user, 'tenant.update'));
    }
}
