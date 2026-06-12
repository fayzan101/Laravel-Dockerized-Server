<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_migrations', function (Blueprint $table) {
            $table->string('migration_type')->default('internal')->after('user_id');
            $table->foreignId('source_tenant_id')->nullable()->after('migration_type')->constrained('tenants')->nullOnDelete();
            $table->foreignId('target_tenant_id')->nullable()->after('source_tenant_id')->constrained('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('data_migrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_tenant_id');
            $table->dropConstrainedForeignId('source_tenant_id');
            $table->dropColumn('migration_type');
        });
    }
};
