<?php

namespace App\Policies;

use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;
use App\Services\RolePermissionService;

class IntegrationPolicy
{
    use HandlesTenantAuthorization;

    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function create(User $user, Tenant $tenant): bool
    {
        return $this->belongsToTenant($user, $tenant->id)
            && $this->permissions->has($user, 'integrations.manage');
    }

    public function view(User $user, Integration $integration): bool
    {
        return $this->belongsToTenant($user, $integration->tenant_id);
    }

    public function update(User $user, Integration $integration): bool
    {
        return $this->belongsToTenant($user, $integration->tenant_id)
            && $this->permissions->has($user, 'integrations.manage');
    }

    public function delete(User $user, Integration $integration): bool
    {
        return $this->update($user, $integration);
    }

    public function test(User $user, Integration $integration): bool
    {
        return $this->view($user, $integration)
            && $this->permissions->has($user, 'integrations.manage');
    }
}
