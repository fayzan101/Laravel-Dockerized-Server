<?php

namespace Tests\Feature\Api\Data;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class DataApiTest extends TestCase
{
    use CreatesTenants;

    public function test_admin_can_export_tenant_data(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/data/export')
            ->assertOk()
            ->assertJsonStructure(['tenant', 'users']);
    }

    public function test_admin_can_import_tenant_data(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/data/import', [
            'data' => [
                'users' => [
                    ['name' => 'Imported User', 'email' => 'imported@example.com', 'role' => 'member'],
                ],
            ],
        ])->assertOk()->assertJsonPath('imported_users', 1);
    }

    public function test_admin_can_start_migration(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/data/migrate')
            ->assertOk()
            ->assertJsonPath('status', 'pending');
    }

    public function test_admin_can_delete_tenant_data(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $this->createMember($tenant);

        $this->actingAsApi($admin)->deleteJson('/api/tenants/' . $tenant->id . '/data')
            ->assertOk();

        $this->assertDatabaseMissing('users', ['tenant_id' => $tenant->id]);
    }

    public function test_member_cannot_export_data(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($member)->getJson('/api/data/export')->assertForbidden();
    }
}
