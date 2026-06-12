<?php

namespace Tests\Feature\Api\Users;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use CreatesTenants;

    public function test_user_can_view_profile(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('email', $admin->email);
    }

    public function test_admin_can_create_tenant_user(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/tenants/' . $tenant->id . '/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'member',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();
    }

    public function test_admin_can_list_tenant_users_by_id(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/tenants/' . $tenant->id . '/users')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_admin_can_update_user(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($admin)->putJson('/api/users/' . $member->id, [
            'name' => 'Updated Member',
        ])->assertOk()->assertJsonPath('user.name', 'Updated Member');
    }

    public function test_admin_can_delete_user(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($admin)->deleteJson('/api/users/' . $member->id)
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }
}
