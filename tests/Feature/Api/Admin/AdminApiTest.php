<?php

namespace Tests\Feature\Api\Admin;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use CreatesTenants;

    public function test_super_admin_can_list_tenants(): void
    {
        $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAsApi($superAdmin)->getJson('/api/admin/tenants');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_super_admin_can_show_tenant(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->getJson('/api/admin/tenants/' . $tenant->id)
            ->assertOk()
            ->assertJsonPath('id', $tenant->id);
    }

    public function test_tenant_admin_cannot_list_all_tenants(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/admin/tenants')->assertForbidden();
    }

    public function test_super_admin_can_view_real_tenant_usage(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->getJson('/api/admin/tenants/' . $tenant->id . '/usage')
            ->assertOk()
            ->assertJsonStructure([
                'tenant_id',
                'tenant',
                'usage' => [
                    'users',
                    'integrations',
                    'usage_records',
                    'usage_by_feature',
                    'feature_overrides',
                    'audit_logs',
                    'activity_logs',
                ],
            ])
            ->assertJsonPath('usage.users', 1);
    }

    public function test_super_admin_can_suspend_and_reactivate_tenant(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->postJson('/api/admin/tenants/' . $tenant->id . '/suspend')
            ->assertOk();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'suspended']);

        $this->actingAsApi($superAdmin)->postJson('/api/admin/tenants/' . $tenant->id . '/reactivate')
            ->assertOk();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'active']);
    }

    public function test_super_admin_impersonation_returns_token(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->postJson('/api/admin/impersonate-user', [
            'user_id' => $admin->id,
        ])->assertOk()->assertJsonStructure(['access_token', 'user']);
    }

    public function test_super_admin_can_create_and_delete_tenant(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAsApi($superAdmin)->postJson('/api/admin/tenants', [
            'name' => 'New Co',
            'slug' => 'new-co-' . uniqid(),
            'owner_name' => 'Owner',
            'owner_email' => 'owner-' . uniqid() . '@example.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ])->assertCreated();

        $tenantId = $response->json('tenant.id');

        $this->actingAsApi($superAdmin)->deleteJson('/api/admin/tenants/' . $tenantId)
            ->assertOk();

        $this->assertSoftDeleted('tenants', ['id' => $tenantId]);
    }

    public function test_super_admin_can_view_dashboard(): void
    {
        $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['platform', 'recent_audit_logs', 'tenants_by_status']);
    }
}
