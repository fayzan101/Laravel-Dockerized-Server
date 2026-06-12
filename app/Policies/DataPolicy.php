<?php

namespace App\Policies;

use App\Models\User;
use App\Services\RolePermissionService;

class DataPolicy
{
    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function export(User $user): bool
    {
        return $user->tenant_id !== null
            && $this->permissions->has($user, 'data.export');
    }

    public function import(User $user): bool
    {
        return $user->tenant_id !== null
            && $this->permissions->has($user, 'data.import');
    }

    public function migrate(User $user): bool
    {
        return $user->tenant_id !== null
            && $this->permissions->has($user, 'data.migrate');
    }
}
