<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@platform.local'],
            [
                'name' => 'Platform Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin->value,
                'tenant_id' => null,
            ]
        );

        PlatformSetting::set('platform', [
            'maintenance_mode' => false,
            'default_tenant_status' => 'active',
            'max_users_per_tenant' => 100,
            'support_email' => 'support@platform.local',
        ]);
    }
}
