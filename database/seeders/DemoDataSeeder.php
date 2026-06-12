<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'acme-corp'],
            [
                'name' => 'Acme Corp',
                'description' => 'Demo tenant for development',
                'status' => 'active',
                'activated_at' => now(),
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@acme.local'],
            [
                'name' => 'Acme Admin',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => UserRole::Admin->value,
            ]
        );

        $tenant->update(['owner_id' => $admin->id]);

        User::updateOrCreate(
            ['email' => 'member@acme.local'],
            [
                'name' => 'Acme Member',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => UserRole::Member->value,
            ]
        );

        $editorRole = Role::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'editor'],
            ['description' => 'Can edit content']
        );

        $editPermission = Permission::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'content.edit'],
            ['description' => 'Edit tenant content']
        );

        $editorRole->permissions()->syncWithoutDetaching([$editPermission->id]);
    }
}
