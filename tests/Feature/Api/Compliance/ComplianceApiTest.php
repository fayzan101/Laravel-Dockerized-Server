<?php

namespace Tests\Feature\Api\Compliance;

use App\Models\AuditLog;
use App\Models\PlatformSetting;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ComplianceApiTest extends TestCase
{
    use CreatesTenants;

    public function test_admin_can_view_compliance_report(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => 'login',
        ]);

        $this->actingAsApi($admin)->getJson('/api/compliance/report')
            ->assertOk()
            ->assertJsonStructure(['generated_at', 'summary', 'top_audit_actions']);
    }

    public function test_admin_can_bulk_export_audit_logs(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => 'user.updated',
        ]);

        $this->actingAsApi($admin)->getJson('/api/compliance/audit/export')
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_super_admin_can_archive_expired_logs(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => 'old.event',
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);

        $this->actingAsApi($superAdmin)->postJson('/api/compliance/audit/archive')
            ->assertOk()
            ->assertJsonStructure(['message', 'result']);
    }

    public function test_tenant_admin_cannot_archive_logs(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/compliance/audit/archive')
            ->assertForbidden();
    }
}
