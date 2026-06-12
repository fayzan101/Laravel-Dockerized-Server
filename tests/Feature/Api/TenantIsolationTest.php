<?php

namespace Tests\Feature\Api;

use App\Models\Integration;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use CreatesTenants;

    public function test_admin_cannot_access_another_tenants_users(): void
    {
        ['admin' => $adminA] = $this->createTenantWithAdmin();
        ['tenant' => $tenantB] = $this->createTenantWithAdmin();

        $this->actingAsApi($adminA)->getJson('/api/tenants/' . $tenantB->id . '/users')
            ->assertForbidden();
    }

    public function test_admin_cannot_update_user_in_another_tenant(): void
    {
        ['admin' => $adminA] = $this->createTenantWithAdmin();
        ['tenant' => $tenantB] = $this->createTenantWithAdmin();
        $memberB = $this->createMember($tenantB);

        $this->actingAsApi($adminA)->putJson('/api/users/' . $memberB->id, [
            'name' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_integrations_are_tenant_scoped(): void
    {
        ['tenant' => $tenantA, 'admin' => $adminA] = $this->createTenantWithAdmin();
        ['tenant' => $tenantB] = $this->createTenantWithAdmin();

        Integration::create(['tenant_id' => $tenantA->id, 'name' => 'Only A', 'type' => 'webhook']);
        Integration::create(['tenant_id' => $tenantB->id, 'name' => 'Only B', 'type' => 'webhook']);

        $names = collect(
            $this->actingAsApi($adminA)->getJson('/api/integrations')->json('data')
        )->pluck('name');

        $this->assertTrue($names->contains('Only A'));
        $this->assertFalse($names->contains('Only B'));
    }

    public function test_suspended_tenant_is_blocked_from_api(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $tenant->update(['status' => 'suspended']);

        $this->actingAsApi($admin)->getJson('/api/tenant/current')->assertForbidden();
    }
}
