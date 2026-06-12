<?php

namespace Tests\Feature\Api\Iam;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class IamApiTest extends TestCase
{
    use CreatesTenants;

    public function test_authenticated_user_can_list_system_roles(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/roles')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_admin_can_view_user_permissions(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($admin)->getJson('/api/users/' . $member->id . '/permissions')
            ->assertOk()
            ->assertJsonStructure(['user_id', 'role', 'permissions'])
            ->assertJsonPath('role', 'member');
    }

    public function test_member_cannot_view_other_user_permissions(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($member)->getJson('/api/users/' . $admin->id . '/permissions')
            ->assertForbidden();
    }
}
