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

        $this->actingAsApi($superAdmin)->getJson('/api/admin/tenants')
            ->assertOk()
            ->assertJson(fn ($json) => $json->count() >= 1);
    }

    public function test_tenant_admin_cannot_list_all_tenants(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/admin/tenants')->assertForbidden();
    }

    public function test_super_admin_can_view_tenant_usage(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->getJson('/api/admin/tenants/' . $tenant->id . '/usage')
            ->assertOk()
            ->assertJsonStructure(['tenant_id', 'usage']);
    }

    public function test_super_admin_can_suspend_tenant(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->postJson('/api/admin/tenants/' . $tenant->id . '/suspend')
            ->assertOk();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'suspended']);
    }

    public function test_super_admin_impersonation_returns_token(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->postJson('/api/admin/impersonate-user', [
            'user_id' => $admin->id,
        ])->assertOk()->assertJsonStructure(['access_token', 'user']);
    }
}
