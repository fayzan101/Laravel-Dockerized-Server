<?php

namespace App\Services;

use App\Models\User;

class RolePermissionService
{
    public function forUser(User $user): array
    {
        $system = config('permissions.' . $user->role, []);

        if (! $user->tenant_id) {
            return $system;
        }

        $custom = $user->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('name')
            ->all();

        return array_values(array_unique(array_merge($system, $custom)));
    }

    public function has(User $user, string $permission): bool
    {
        return in_array($permission, $this->forUser($user), true);
    }
}
