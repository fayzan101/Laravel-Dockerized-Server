<?php

namespace Tests\Feature\Api\Platform;

use App\Models\Integration;
use App\Models\PlatformSetting;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class PlatformLimitsTest extends TestCase
{
    use CreatesTenants;

    public function test_invite_rejects_when_platform_user_limit_reached(): void
    {
        $this->seedPlatformSettings(['max_users_per_tenant' => 1]);
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/tenant/invite-user', [
            'email' => 'newuser@example.com',
            'role' => 'member',
        ])->assertStatus(422);
    }

    public function test_integration_create_rejects_when_feature_limit_reached(): void
    {
        $this->ensureFeature('integrations', ['default_limit' => 1]);
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        Integration::create([
            'tenant_id' => $tenant->id,
            'name' => 'Existing',
            'type' => 'webhook',
            'config' => ['url' => 'https://example.com/hook'],
        ]);

        $this->actingAsApi($admin)->postJson('/api/tenants/' . $tenant->id . '/integrations', [
            'name' => 'Second',
            'type' => 'webhook',
            'config' => ['url' => 'https://example.com/hook2'],
        ])->assertStatus(422);
    }

    public function test_super_admin_can_update_platform_user_limit(): void
    {
        $this->seedPlatformSettings(['max_users_per_tenant' => 100]);
        $superAdmin = $this->createSuperAdmin();

        $this->actingAsApi($superAdmin)->putJson('/api/admin/settings', [
            'settings' => [
                'max_users_per_tenant' => 50,
                'maintenance_mode' => false,
            ],
        ])->assertOk()->assertJsonPath('settings.max_users_per_tenant', 50);

        $this->assertEquals(50, PlatformSetting::get('platform')['max_users_per_tenant']);
    }
}
