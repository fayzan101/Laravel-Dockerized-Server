<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use CreatesTenants;

    public function test_register_creates_user_and_tenant(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Acme Corp',
            'tenant_slug' => 'acme-corp',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'user', 'tenant']);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => UserRole::Admin->value,
        ]);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        ['tenant' => $tenant, 'admin' => $admin] = $this->createTenantWithAdmin([
            'slug' => 'login-tenant',
        ], [
            'email' => 'login@example.com',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
            'tenant_slug' => $tenant->slug,
        ])->assertOk()->assertJsonStructure(['access_token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->createTenantWithAdmin([], ['email' => 'fail@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'fail@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_logout_revokes_token(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_refresh_issues_new_token(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->postJson('/api/auth/refresh')
            ->assertOk()
            ->assertJsonStructure(['access_token']);
    }

    public function test_sso_validates_bearer_token(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();
        $token = $admin->createToken('sso')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/sso')
            ->assertOk()
            ->assertJsonPath('user.id', $admin->id);
    }

    public function test_forgot_password_accepts_valid_email(): void
    {
        $this->createTenantWithAdmin([], ['email' => 'reset@example.com']);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertOk();
    }
}
