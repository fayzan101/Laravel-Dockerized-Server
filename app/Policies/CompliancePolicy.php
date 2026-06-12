<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;
use App\Services\RolePermissionService;

class CompliancePolicy
{
    use HandlesTenantAuthorization;

    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function viewAuditLogs(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->permissions->has($user, 'audit.view');
    }

    public function viewActivityLogs(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function gdprExport(User $user): bool
    {
        return $this->permissions->has($user, 'compliance.gdpr');
    }

    public function gdprDelete(User $user): bool
    {
        return $this->permissions->has($user, 'compliance.gdpr');
    }

    public function exportAuditLogs(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->permissions->has($user, 'audit.view');
    }

    public function viewComplianceReport(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->permissions->has($user, 'audit.view');
    }

    public function archiveLogs(User $user): bool
    {
        return $this->isSuperAdmin($user)
            && $this->permissions->has($user, 'audit.view_all');
    }
}
