<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\PlatformSetting;
use App\Models\User;

class PlatformLimitService
{
    public function __construct(private FeatureLimitService $features)
    {
    }

    public function settings(): array
    {
        return PlatformSetting::get('platform', [
            'max_users_per_tenant' => 100,
        ]);
    }

    public function maxUsersPerTenant(): int
    {
        return (int) ($this->settings()['max_users_per_tenant'] ?? 100);
    }

    public function userCount(int $tenantId): int
    {
        return User::where('tenant_id', $tenantId)->count();
    }

    public function integrationCount(int $tenantId): int
    {
        return Integration::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();
    }

    public function assertCanAddUser(int $tenantId, int $adding = 1): void
    {
        $max = $this->maxUsersPerTenant();
        $current = $this->userCount($tenantId);

        if ($current + $adding > $max) {
            abort(422, "Tenant user limit reached. Maximum is {$max}, current count is {$current}.");
        }
    }

    public function assertCanAddIntegration(int $tenantId, int $adding = 1): void
    {
        if (! $this->features->isEnabledForTenant($tenantId, 'integrations')) {
            abort(422, 'Integrations are disabled for this tenant.');
        }

        $limit = $this->features->limitForTenant($tenantId, 'integrations');

        if ($limit === null) {
            return;
        }

        $current = $this->integrationCount($tenantId);

        if ($current + $adding > $limit) {
            abort(422, "Integration limit reached. Maximum is {$limit}.");
        }
    }
}
