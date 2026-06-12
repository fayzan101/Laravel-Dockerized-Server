<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FeatureSeeder::class,
            SuperAdminSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
