<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\TenantFeatureOverride;
use App\Models\UsageRecord;
use App\Models\User;

class FeatureLimitService
{
    public function isEnabled(User $user, string $featureKey): bool
    {
        if (! $user->tenant_id) {
            return true;
        }

        $feature = Feature::where('key', $featureKey)->first();

        if (! $feature) {
            return true;
        }

        $override = TenantFeatureOverride::where('tenant_id', $user->tenant_id)
            ->where('feature_key', $featureKey)
            ->first();

        if ($override && $override->enabled !== null) {
            return (bool) $override->enabled;
        }

        return (bool) $feature->default_enabled;
    }

    public function limitFor(User $user, string $featureKey): ?int
    {
        if (! $user->tenant_id) {
            return null;
        }

        $feature = Feature::where('key', $featureKey)->first();

        if (! $feature) {
            return null;
        }

        $override = TenantFeatureOverride::where('tenant_id', $user->tenant_id)
            ->where('feature_key', $featureKey)
            ->first();

        if ($override && $override->limit !== null) {
            return $override->limit;
        }

        return $feature->default_limit;
    }

    public function currentUsage(int $tenantId, string $featureKey): int
    {
        return (int) UsageRecord::where('tenant_id', $tenantId)
            ->where('feature_key', $featureKey)
            ->sum('amount');
    }

    public function assertCanReport(User $user, string $featureKey, int $amount): void
    {
        if (! $this->isEnabled($user, $featureKey)) {
            abort(422, 'Feature is disabled for this tenant.');
        }

        $limit = $this->limitFor($user, $featureKey);

        if ($limit === null) {
            return;
        }

        $current = $this->currentUsage($user->tenant_id, $featureKey);

        if ($current + $amount > $limit) {
            abort(422, "Feature limit exceeded for {$featureKey}.");
        }
    }
}
