<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['key' => 'api_calls', 'name' => 'API Calls', 'description' => 'Outbound API call quota', 'default_enabled' => true, 'default_limit' => 10000],
            ['key' => 'storage_gb', 'name' => 'Storage', 'description' => 'Storage in GB', 'default_enabled' => true, 'default_limit' => 50],
            ['key' => 'users', 'name' => 'Team Members', 'description' => 'Maximum team members', 'default_enabled' => true, 'default_limit' => 25],
            ['key' => 'integrations', 'name' => 'Integrations', 'description' => 'Active integrations', 'default_enabled' => true, 'default_limit' => 10],
            ['key' => 'exports', 'name' => 'Data Exports', 'description' => 'Monthly data exports', 'default_enabled' => true, 'default_limit' => 100],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(['key' => $feature['key']], $feature);
        }
    }
}
