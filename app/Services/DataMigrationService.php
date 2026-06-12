<?php

namespace App\Services;

use App\Models\DataMigration;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DataMigrationService
{
    public function __construct(
        private ExportStorageService $exports,
        private PlatformLimitService $limits
    ) {
    }

    public function queue(User $user, string $type = 'internal', ?int $sourceTenantId = null, ?int $targetTenantId = null): DataMigration
    {
        $migration = DataMigration::create([
            'tenant_id' => $user->tenant_id ?? $sourceTenantId,
            'user_id' => $user->id,
            'migration_type' => $type,
            'source_tenant_id' => $sourceTenantId ?? $user->tenant_id,
            'target_tenant_id' => $targetTenantId,
            'status' => 'pending',
        ]);

        return $migration;
    }

    public function process(DataMigration $migration): DataMigration
    {
        $migration->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $steps = [];

        try {
            if ($migration->migration_type === 'cross_tenant') {
                $steps = $this->runCrossTenantMigration($migration);
            } else {
                $steps = $this->runInternalMigration($migration);
            }

            $migration->update([
                'status' => 'completed',
                'steps' => $steps,
                'result' => ['message' => 'Migration completed successfully'],
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $migration->update([
                'status' => 'failed',
                'steps' => $steps,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $migration->fresh();
    }

    /** @deprecated Use queue() + RunDataMigrationJob */
    public function start(User $user): DataMigration
    {
        $migration = $this->queue($user);

        return $this->process($migration);
    }

    private function runInternalMigration(DataMigration $migration): array
    {
        $tenantId = $migration->source_tenant_id ?? $migration->tenant_id;
        $steps = [];

        $steps[] = $this->runStep('validate_tenant', function () use ($tenantId) {
            return Tenant::findOrFail($tenantId)->only(['id', 'name', 'slug', 'status']);
        });

        $steps[] = $this->runStep('snapshot_users', function () use ($tenantId) {
            $tenant = Tenant::with('users')->findOrFail($tenantId);
            $path = 'migrations/tenant-' . $tenant->id . '-' . now()->timestamp . '.json';
            $this->exports->put($path, json_encode([
                'tenant' => $tenant->only(['id', 'name', 'slug']),
                'users_count' => $tenant->users->count(),
                'exported_at' => now()->toIso8601String(),
                'disk' => $this->exports->disk(),
            ], JSON_PRETTY_PRINT));

            return ['snapshot_path' => $path, 'disk' => $this->exports->disk(), 'users_count' => $tenant->users->count()];
        });

        $steps[] = $this->runStep('verify_integrations', function () use ($tenantId) {
            return [
                'integrations' => Integration::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)->count(),
            ];
        });

        return $steps;
    }

    private function runCrossTenantMigration(DataMigration $migration): array
    {
        $sourceId = $migration->source_tenant_id;
        $targetId = $migration->target_tenant_id;
        $steps = [];

        $steps[] = $this->runStep('validate_tenants', function () use ($sourceId, $targetId) {
            return [
                'source' => Tenant::findOrFail($sourceId)->only(['id', 'name', 'slug']),
                'target' => Tenant::findOrFail($targetId)->only(['id', 'name', 'slug']),
            ];
        });

        $steps[] = $this->runStep('export_source_schema', function () use ($sourceId) {
            $tenant = Tenant::with(['users', 'integrations'])->findOrFail($sourceId);
            $path = 'migrations/cross-' . $sourceId . '-to-' . $tenant->id . '-' . now()->timestamp . '.json';
            $this->exports->put($path, json_encode([
                'tenant' => $tenant->only(['id', 'name', 'slug', 'settings']),
                'users' => $tenant->users->map->only(['name', 'email', 'role']),
                'integrations' => $tenant->integrations->map->only(['name', 'type', 'config', 'is_active']),
                'disk' => $this->exports->disk(),
            ], JSON_PRETTY_PRINT));

            return ['snapshot_path' => $path, 'disk' => $this->exports->disk(), 'users' => $tenant->users->count()];
        });

        $steps[] = $this->runStep('import_to_target', function () use ($sourceId, $targetId) {
            $source = Tenant::with('integrations')->findOrFail($sourceId);
            $importedIntegrations = 0;

            $newIntegrations = $source->integrations->filter(function ($integration) use ($targetId) {
                return ! Integration::withoutGlobalScopes()
                    ->where('tenant_id', $targetId)
                    ->where('name', $integration->name)
                    ->exists();
            });

            $this->limits->assertCanAddIntegration($targetId, $newIntegrations->count());

            foreach ($source->integrations as $integration) {
                Integration::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $targetId, 'name' => $integration->name],
                    [
                        'type' => $integration->type,
                        'config' => $integration->config,
                        'is_active' => $integration->is_active,
                    ]
                );
                $importedIntegrations++;
            }

            return ['imported_integrations' => $importedIntegrations];
        });

        return $steps;
    }

    private function runStep(string $name, callable $callback): array
    {
        return [
            'step' => $name,
            'status' => 'completed',
            'result' => DB::transaction(fn () => $callback()),
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
