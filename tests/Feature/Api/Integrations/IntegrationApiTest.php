<?php

namespace Tests\Feature\Api\Integrations;

use App\Models\Integration;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use CreatesTenants;

    public function test_admin_can_create_integration(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/tenants/' . $tenant->id . '/integrations', [
            'name' => 'Slack',
            'type' => 'webhook',
            'config' => ['url' => 'https://hooks.slack.com/test'],
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('integration.name', 'Slack');
    }

    public function test_admin_can_show_update_and_delete_integration(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $integration = Integration::create([
            'tenant_id' => $tenant->id,
            'name' => 'Slack',
            'type' => 'webhook',
            'config' => ['url' => 'https://example.com'],
        ]);

        $this->actingAsApi($admin)->getJson('/api/integrations/' . $integration->id)
            ->assertOk()
            ->assertJsonPath('integration.name', 'Slack');

        $this->actingAsApi($admin)->putJson('/api/integrations/' . $integration->id, [
            'name' => 'Slack Updated',
            'is_active' => false,
        ])->assertOk()->assertJsonPath('integration.name', 'Slack Updated');

        $this->actingAsApi($admin)->deleteJson('/api/integrations/' . $integration->id)
            ->assertOk();

        $this->assertDatabaseMissing('integrations', ['id' => $integration->id]);
    }

    public function test_user_only_sees_own_tenant_integrations(): void
    {
        ['tenant' => $tenantA, 'admin' => $adminA] = $this->createTenantWithAdmin();
        ['tenant' => $tenantB] = $this->createTenantWithAdmin();

        Integration::create(['tenant_id' => $tenantA->id, 'name' => 'A', 'type' => 'webhook']);
        Integration::create(['tenant_id' => $tenantB->id, 'name' => 'B', 'type' => 'webhook']);

        $names = collect(
            $this->actingAsApi($adminA)->getJson('/api/integrations')->json('data')
        )->pluck('name');

        $this->assertTrue($names->contains('A'));
        $this->assertFalse($names->contains('B'));
    }

    public function test_webhook_test_requires_url(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $integration = Integration::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bad Webhook',
            'type' => 'webhook',
            'config' => [],
        ]);

        $this->actingAsApi($admin)->postJson('/api/integrations/' . $integration->id . '/test')
            ->assertStatus(422);
    }

    public function test_member_cannot_create_integration(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($member)->postJson('/api/tenants/' . $tenant->id . '/integrations', [
            'name' => 'Slack',
            'type' => 'webhook',
        ])->assertForbidden();
    }
}
