<?php

namespace App\Policies;

use App\Models\User;
use App\Services\RolePermissionService;

class UsagePolicy
{
    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function view(User $user): bool
    {
        return $user->tenant_id !== null
            && $this->permissions->has($user, 'usage.view');
    }

    public function report(User $user): bool
    {
        return $user->tenant_id !== null
            && $this->permissions->has($user, 'usage.report');
    }
}
