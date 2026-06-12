<?php

namespace Tests\Feature\Api\Data;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class DataApiTest extends TestCase
{
    use CreatesTenants;

    public function test_admin_can_export_tenant_data(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/data/export')
            ->assertOk()
            ->assertJsonStructure(['exported_at', 'tenant', 'users']);
    }

    public function test_admin_can_download_export(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/data/export?download=1')
            ->assertOk()
            ->assertHeader('content-disposition');
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

    public function test_admin_can_run_migration_with_steps(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $response = $this->actingAsApi($admin)->postJson('/api/data/migrate');

        $response->assertOk();
        $this->assertEquals('completed', $response->json('migration.status'));
        $this->assertNotEmpty($response->json('migration.steps'));
    }

    public function test_admin_can_list_migrations(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();
        $this->actingAsApi($admin)->postJson('/api/data/migrate');

        $this->actingAsApi($admin)->getJson('/api/data/migrations')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page']);
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
