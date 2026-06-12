<?php

namespace App\Policies;

use App\Models\Feature;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;

class FeaturePolicy
{
    use HandlesTenantAuthorization;

    public function manageCatalog(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->manageCatalog($user);
    }

    public function update(User $user, Feature $feature): bool
    {
        return $this->manageCatalog($user);
    }
}
