<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function audit(
        string $action,
        ?User $user = null,
        ?int $tenantId = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $metadata = []
    ): AuditLog {
        return AuditLog::create([
            'tenant_id' => $tenantId ?? $user?->tenant_id,
            'user_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
        ]);
    }

    public function activity(
        string $activity,
        ?User $user = null,
        ?Model $subject = null,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::create([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'activity' => $activity,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
        ]);
    }
}
