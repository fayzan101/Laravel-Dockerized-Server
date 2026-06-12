<?php

namespace Tests\Feature\Api\Usage;

use App\Models\UsageRecord;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class UsageApiTest extends TestCase
{
    use CreatesTenants;

    public function test_user_can_view_usage_summary(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();

        UsageRecord::create([
            'tenant_id' => $tenant->id,
            'feature_key' => 'api_calls',
            'amount' => 5,
            'recorded_at' => now(),
        ]);

        $this->actingAsApi($admin)->getJson('/api/usage')
            ->assertOk()
            ->assertJsonStructure(['tenant_id', 'summary', 'records_count', 'records' => ['data']]);
    }

    public function test_admin_can_report_usage(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();
        $this->createFeature(['key' => 'api_calls', 'default_limit' => 100]);

        $this->actingAsApi($admin)->postJson('/api/usage/report', [
            'feature_key' => 'api_calls',
            'amount' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('usage_records', [
            'tenant_id' => $admin->tenant_id,
            'feature_key' => 'api_calls',
            'amount' => 3,
        ]);
    }

    public function test_usage_report_rejects_when_limit_exceeded(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin();
        $this->createFeature(['key' => 'api_calls', 'default_limit' => 5]);

        UsageRecord::create([
            'tenant_id' => $tenant->id,
            'feature_key' => 'api_calls',
            'amount' => 4,
            'recorded_at' => now(),
        ]);

        $this->actingAsApi($admin)->postJson('/api/usage/report', [
            'feature_key' => 'api_calls',
            'amount' => 2,
        ])->assertStatus(422);
    }

    public function test_member_cannot_report_usage(): void
    {
        ['tenant' => $tenant] = $this->createTenantWithAdmin();
        $member = $this->createMember($tenant);

        $this->actingAsApi($member)->postJson('/api/usage/report', [
            'feature_key' => 'api_calls',
            'amount' => 1,
        ])->assertForbidden();
    }
}
