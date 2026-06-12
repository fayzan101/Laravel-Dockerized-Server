<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\GdprRequest;
use App\Models\Tenant;
use App\Models\User;

class ComplianceReportService
{
    public function generate(?int $tenantId = null): array
    {
        $tenantQuery = Tenant::query();
        $userQuery = User::query();
        $auditQuery = AuditLog::query();
        $activityQuery = ActivityLog::query();
        $gdprQuery = GdprRequest::query();

        if ($tenantId) {
            $userQuery->where('tenant_id', $tenantId);
            $auditQuery->where('tenant_id', $tenantId);
            $activityQuery->where('tenant_id', $tenantId);
            $gdprQuery->where('tenant_id', $tenantId);
        }

        $since30 = now()->subDays(30);

        return [
            'generated_at' => now()->toIso8601String(),
            'scope' => $tenantId ? 'tenant' : 'platform',
            'tenant_id' => $tenantId,
            'summary' => [
                'tenants' => $tenantId ? 1 : $tenantQuery->count(),
                'active_tenants' => $tenantId
                    ? (Tenant::find($tenantId)?->status === 'active' ? 1 : 0)
                    : $tenantQuery->where('status', 'active')->count(),
                'suspended_tenants' => $tenantId
                    ? (Tenant::find($tenantId)?->status === 'suspended' ? 1 : 0)
                    : $tenantQuery->where('status', 'suspended')->count(),
                'users' => $userQuery->count(),
                'audit_logs_total' => (clone $auditQuery)->count(),
                'audit_logs_last_30_days' => (clone $auditQuery)->where('created_at', '>=', $since30)->count(),
                'activity_logs_total' => (clone $activityQuery)->count(),
                'activity_logs_last_30_days' => (clone $activityQuery)->where('created_at', '>=', $since30)->count(),
                'gdpr_requests_total' => (clone $gdprQuery)->count(),
                'gdpr_exports' => (clone $gdprQuery)->where('type', 'export')->count(),
                'gdpr_deletes' => (clone $gdprQuery)->where('type', 'delete')->count(),
            ],
            'top_audit_actions' => (clone $auditQuery)
                ->selectRaw('action, count(*) as total')
                ->groupBy('action')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'action'),
            'retention_days' => config('audit.retention_days', 90),
        ];
    }
}
