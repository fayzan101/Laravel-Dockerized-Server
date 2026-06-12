<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait HandlesTenantAuthorization
{
    protected function isSuperAdmin(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin->value;
    }

    protected function isTenantAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin->value;
    }

    protected function belongsToTenant(User $user, ?int $tenantId): bool
    {
        return $user->tenant_id !== null && $user->tenant_id === $tenantId;
    }

    protected function sameTenant(User $user, User $target): bool
    {
        return $user->tenant_id !== null && $user->tenant_id === $target->tenant_id;
    }
}
