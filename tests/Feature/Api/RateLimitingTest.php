<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use CreatesTenants;

    public function test_login_is_rate_limited(): void
    {
        RateLimiter::clear('127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong',
            ]);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    public function test_list_endpoints_are_rate_limited(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();
        RateLimiter::clear((string) $admin->id);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAsApi($admin)->getJson('/api/tenant/users');
        }

        $this->actingAsApi($admin)->getJson('/api/tenant/users')->assertStatus(429);
    }
}
