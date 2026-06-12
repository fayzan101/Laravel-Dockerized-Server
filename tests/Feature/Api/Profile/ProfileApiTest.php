<?php

namespace Tests\Feature\Api\Profile;

use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use CreatesTenants;

    public function test_user_can_view_profile(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('email', $admin->email);
    }

    public function test_user_can_update_profile(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.email', 'updated@example.com');
    }

    public function test_user_can_change_password(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->putJson('/api/user/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();

        $admin->refresh();
        $this->assertTrue(Hash::check('newpassword123', $admin->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        ['admin' => $admin] = $this->createTenantWithAdmin();

        $this->actingAsApi($admin)->putJson('/api/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }
}
