<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\GdprRequest;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\TenantFeatureOverride;
use App\Models\UsageRecord;

class TenantUsageService
{
    public function metricsFor(Tenant $tenant): array
    {
        $usageByFeature = UsageRecord::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->selectRaw('feature_key, SUM(amount) as total')
            ->groupBy('feature_key')
            ->pluck('total', 'feature_key');

        return [
            'tenant_id' => $tenant->id,
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
            ],
            'usage' => [
                'users' => $tenant->users()->count(),
                'integrations' => Integration::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
                'active_integrations' => Integration::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)->where('is_active', true)->count(),
                'usage_records' => UsageRecord::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
                'usage_by_feature' => $usageByFeature,
                'feature_overrides' => TenantFeatureOverride::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)->count(),
                'audit_logs' => AuditLog::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
                'activity_logs' => ActivityLog::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
                'gdpr_requests' => GdprRequest::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
