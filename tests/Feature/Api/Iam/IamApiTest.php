<?php

namespace Tests\Feature\Api\Iam;

use App\Models\Permission;
use App\Models\Role;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class IamApiTest extends TestCase
{
    use CreatesTenants;

    public function test_authenticated_user_can_list_system_and_custom_roles(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        Role::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'editor',
            'description' => 'Content editor',
        ]);

        $response = $this->actingAsApi($admin)->getJson('/api/roles')->assertOk();

        $this->assertCount(2, $response->json('system_roles'));
        $this->assertGreaterThanOrEqual(1, count($response->json('custom_roles.data')));
    }

    public function test_admin_can_create_role_and_permission(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $perm = $this->actingAsApi($admin)->postJson('/api/permissions', [
            'name' => 'content.edit',
            'description' => 'Edit content',
        ])->assertCreated()->json('permission');

        $this->actingAsApi($admin)->postJson('/api/roles', [
            'name' => 'editor',
            'description' => 'Editor role',
            'permission_ids' => [$perm['id']],
        ])->assertCreated();

        $this->assertDatabaseHas('roles', ['name' => 'editor', 'tenant_id' => $admin->tenant_id]);
    }

    public function test_admin_can_assign_custom_roles_to_user(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $role = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'editor',
        ]);

        $this->actingAsApi($admin)->putJson('/api/users/' . $member->id . '/roles', [
            'role_ids' => [$role->id],
        ])->assertOk();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $member->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_admin_can_view_user_permissions_with_custom_roles(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $permission = Permission::create([
            'tenant_id' => $tenant->id,
            'name' => 'content.publish',
        ]);

        $role = Role::create(['tenant_id' => $tenant->id, 'name' => 'publisher']);
        $role->permissions()->attach($permission->id);
        $member->roles()->attach($role->id);

        $this->actingAsApi($admin)->getJson('/api/users/' . $member->id . '/permissions')
            ->assertOk()
            ->assertJsonStructure(['user_id', 'system_role', 'custom_roles', 'permissions'])
            ->assertJsonFragment(['name' => 'content.publish']);
    }

    public function test_member_cannot_create_roles(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($member)->postJson('/api/roles', [
            'name' => 'hacker',
        ])->assertForbidden();
    }

    public function test_admin_can_update_and_delete_role(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $role = Role::create(['tenant_id' => $tenant->id, 'name' => 'editor']);

        $this->actingAsApi($admin)->putJson('/api/roles/' . $role->id, [
            'name' => 'senior-editor',
        ])->assertOk();

        $this->actingAsApi($admin)->deleteJson('/api/roles/' . $role->id)
            ->assertOk();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_can_update_system_role(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($admin)->putJson('/api/users/' . $member->id . '/system-role', [
            'role' => 'admin',
        ])->assertOk()->assertJsonPath('system_role', 'admin');
    }
}
