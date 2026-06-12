<?php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait CreatesTenants
{
    protected function createTenantWithAdmin(array $tenantAttrs = [], array $userAttrs = []): array
    {
        $tenant = Tenant::create(array_merge([
            'name' => 'Acme Corp',
            'slug' => 'acme-' . uniqid(),
            'status' => 'active',
            'activated_at' => now(),
        ], $tenantAttrs));

        $admin = User::create(array_merge([
            'name' => 'Admin User',
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $tenant->id,
            'role' => UserRole::Admin->value,
        ], $userAttrs));

        $tenant->update(['owner_id' => $admin->id]);

        return compact('tenant', 'admin');
    }

    protected function createMember(Tenant $tenant, array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Member User',
            'email' => 'member-' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $tenant->id,
            'role' => UserRole::Member->value,
        ], $attrs));
    }

    protected function createSuperAdmin(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Super Admin',
            'email' => 'super-' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'tenant_id' => null,
            'role' => UserRole::SuperAdmin->value,
        ], $attrs));
    }

    protected function createFeature(array $attrs = []): Feature
    {
        return Feature::create(array_merge([
            'key' => 'feature-' . uniqid(),
            'name' => 'Test Feature',
            'description' => 'Test feature description',
            'default_enabled' => true,
            'default_limit' => 100,
        ], $attrs));
    }

    protected function actingAsApi(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }
}
