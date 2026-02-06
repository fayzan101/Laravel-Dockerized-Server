<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::create('tenant_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('feature_key');
            $table->boolean('enabled')->nullable();
            $table->integer('limit')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'feature_key']);
        });
    }

        public function down(): void
    {
        Schema::dropIfExists('tenant_feature_overrides');
    }
};
