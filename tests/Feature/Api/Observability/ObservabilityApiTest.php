<?php

namespace Tests\Feature\Api\Observability;

use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ObservabilityApiTest extends TestCase
{
    use CreatesTenants;

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_status_endpoint_returns_database_state(): void
    {
        $this->getJson('/api/status')
            ->assertOk()
            ->assertJsonStructure(['status', 'timestamp', 'database']);
    }

    public function test_metrics_endpoint_returns_counts(): void
    {
        $this->createTenantWithAdmin();

        $this->getJson('/api/metrics')
            ->assertOk()
            ->assertJsonStructure(['tenants', 'users', 'timestamp']);
    }

    public function test_admin_can_view_tenant_metrics(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/tenants/' . $tenant->id . '/metrics')
            ->assertOk()
            ->assertJsonStructure(['tenant', 'users', 'timestamp']);
    }

    public function test_member_cannot_view_tenant_metrics(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($member)->getJson('/api/tenants/' . $tenant->id . '/metrics')
            ->assertForbidden();
    }
}
