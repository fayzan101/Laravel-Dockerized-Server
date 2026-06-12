<?php

namespace Tests\Feature\Api\Features;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class FeatureApiTest extends TestCase
{
    use CreatesTenants;

    public function test_authenticated_user_can_list_features(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();
        $this->createFeature(['key' => 'projects']);

        $this->actingAsApi($admin)->getJson('/api/features')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'projects');
    }

    public function test_tenant_user_can_view_tenant_features(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $this->createFeature(['key' => 'api_calls', 'name' => 'API Calls']);

        $this->actingAsApi($admin)->getJson('/api/tenants/' . $tenant->id . '/features')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'api_calls');
    }

    public function test_admin_can_override_tenant_feature(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $this->createFeature(['key' => 'storage']);

        $this->actingAsApi($admin)->postJson('/api/tenants/' . $tenant->id . '/features/override', [
            'feature_key' => 'storage',
            'enabled' => false,
            'limit' => 10,
        ])->assertOk();

        $this->assertDatabaseHas('tenant_feature_overrides', [
            'tenant_id' => $tenant->id,
            'feature_key' => 'storage',
            'enabled' => false,
        ]);
    }

    public function test_member_cannot_override_features(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);
        $this->createFeature(['key' => 'storage']);

        $this->actingAsApi($member)->postJson('/api/tenants/' . $tenant->id . '/features/override', [
            'feature_key' => 'storage',
            'enabled' => false,
        ])->assertForbidden();
    }
}
