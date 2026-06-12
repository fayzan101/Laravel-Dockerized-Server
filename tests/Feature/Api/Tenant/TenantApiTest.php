<?php

namespace Tests\Feature\Api\Tenant;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use CreatesTenants;

    public function test_admin_can_get_current_tenant(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/tenant/current')
            ->assertOk()
            ->assertJsonPath('id', $tenant->id);
    }

    public function test_admin_can_update_tenant(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->putJson('/api/tenant/update', [
            'name' => 'Updated Corp',
        ])->assertOk()->assertJsonPath('tenant.name', 'Updated Corp');
    }

    public function test_admin_can_invite_user(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/tenant/invite-user', [
            'email' => 'invited@example.com',
            'role' => 'member',
        ])->assertCreated();
    }

    public function test_admin_can_list_tenant_users(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $this->createMember($tenant);

        $this->actingAsApi($admin)->getJson('/api/tenant/users')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_remove_user_from_tenant(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($admin)->postJson('/api/tenant/remove-user', [
            'user_id' => $member->id,
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_member_cannot_update_tenant(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($member)->putJson('/api/tenant/update', [
            'name' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_owner_can_transfer_ownership(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($admin)->postJson('/api/tenant/transfer-ownership', [
            'user_id' => $member->id,
        ])->assertOk()->assertJsonPath('owner.id', $member->id);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'owner_id' => $member->id,
        ]);
    }

    public function test_owner_can_soft_delete_tenant(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->deleteJson('/api/tenant')
            ->assertOk();

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }
}
