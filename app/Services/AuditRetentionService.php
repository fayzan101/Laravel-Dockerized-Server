<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;

class AuditRetentionService
{
    public function __construct(private ExportStorageService $exports)
    {
    }

    public function retentionDays(): int
    {
        return (int) config('audit.retention_days', 90);
    }

    public function archiveExpired(): array
    {
        $cutoff = now()->subDays($this->retentionDays());
        $archived = ['audit_logs' => 0, 'activity_logs' => 0, 'disk' => $this->exports->disk()];

        $auditLogs = AuditLog::where('created_at', '<', $cutoff)->get();

        if ($auditLogs->isNotEmpty()) {
            $path = 'audit/archives/audit-' . now()->timestamp . '.json';
            $this->exports->put($path, $auditLogs->toJson(JSON_PRETTY_PRINT));
            $archived['audit_logs'] = AuditLog::where('created_at', '<', $cutoff)->delete();
            $archived['audit_archive_path'] = $path;
        }

        $activityLogs = ActivityLog::where('created_at', '<', $cutoff)->get();

        if ($activityLogs->isNotEmpty()) {
            $path = 'audit/archives/activity-' . now()->timestamp . '.json';
            $this->exports->put($path, $activityLogs->toJson(JSON_PRETTY_PRINT));
            $archived['activity_logs'] = ActivityLog::where('created_at', '<', $cutoff)->delete();
            $archived['activity_archive_path'] = $path;
        }

        return $archived;
    }

    public function exportBulk(?int $tenantId = null, ?string $from = null, ?string $to = null): array
    {
        $auditQuery = AuditLog::query()->latest();
        $activityQuery = ActivityLog::query()->latest();

        if ($tenantId) {
            $auditQuery->where('tenant_id', $tenantId);
            $activityQuery->where('tenant_id', $tenantId);
        }

        if ($from) {
            $auditQuery->where('created_at', '>=', $from);
            $activityQuery->where('created_at', '>=', $from);
        }

        if ($to) {
            $auditQuery->where('created_at', '<=', $to);
            $activityQuery->where('created_at', '<=', $to);
        }

        return [
            'exported_at' => now()->toIso8601String(),
            'tenant_id' => $tenantId,
            'disk' => $this->exports->disk(),
            'audit_logs' => $auditQuery->limit(5000)->get(),
            'activity_logs' => $activityQuery->limit(5000)->get(),
        ];
    }
}
