<?php

namespace Tests\Feature\Api\Audit;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class AuditApiTest extends TestCase
{
    use CreatesTenants;

    public function test_user_can_view_tenant_audit_logs(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => 'login',
        ]);

        $this->actingAsApi($admin)->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonFragment(['action' => 'login']);
    }

    public function test_user_can_view_tenant_specific_audit_logs(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => 'user.invited',
        ]);

        $this->actingAsApi($admin)->getJson('/api/tenants/' . $tenant->id . '/audit-logs')
            ->assertOk()
            ->assertJsonFragment(['action' => 'user.invited']);
    }

    public function test_user_can_view_activity_logs(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'activity' => 'tenant.updated',
        ]);

        $this->actingAsApi($admin)->getJson('/api/activity-logs')
            ->assertOk()
            ->assertJsonFragment(['activity' => 'tenant.updated']);
    }

    public function test_user_can_request_gdpr_export(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/compliance/gdpr/export')
            ->assertOk()
            ->assertJsonPath('status', 'completed');
    }
}
