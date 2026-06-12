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
        ])->assertCreated()->assertJsonPath('integration.name', 'Slack');
    }

    public function test_user_only_sees_own_tenant_integrations(): void
    {
        ['tenant' => $tenantA, 'admin' => $adminA] = $this->createTenantWithAdmin();
        ['tenant' => $tenantB] = $this->createTenantWithAdmin();

        Integration::create(['tenant_id' => $tenantA->id, 'name' => 'A', 'type' => 'webhook']);
        Integration::create(['tenant_id' => $tenantB->id, 'name' => 'B', 'type' => 'webhook']);

        $response = $this->actingAsApi($adminA)->getJson('/api/integrations')->assertOk();
        $names = collect($response->json('integrations'))->pluck('name');

        $this->assertTrue($names->contains('A'));
        $this->assertFalse($names->contains('B'));
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
