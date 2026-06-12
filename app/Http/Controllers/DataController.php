<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Data\ImportDataRequest;
use App\Jobs\RunDataMigrationJob;
use App\Models\DataMigration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DataMigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private DataMigrationService $migrationService
    ) {
    }

    public function export(Request $request)
    {
        $this->authorize('data.export');

        $user = $request->user();
        $tenant = Tenant::with('users')->findOrFail($user->tenant_id);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'tenant' => $tenant,
            'users' => $tenant->users,
        ];

        $this->auditLogger->activity('data.exported', $user, $tenant);

        if ($request->boolean('download')) {
            $filename = 'tenant-' . $tenant->id . '-export-' . now()->timestamp . '.json';

            return response()->streamDownload(function () use ($payload) {
                echo json_encode($payload, JSON_PRETTY_PRINT);
            }, $filename, ['Content-Type' => 'application/json']);
        }

        return response()->json($payload);
    }

    public function import(ImportDataRequest $request)
    {
        $this->authorize('data.import');

        $user = $request->user();
        $imported = 0;

        DB::transaction(function () use ($request, $user, &$imported) {
            foreach ($request->input('data.users', []) as $userData) {
                User::updateOrCreate(
                    [
                        'email' => $userData['email'],
                        'tenant_id' => $user->tenant_id,
                    ],
                    [
                        'name' => $userData['name'],
                        'role' => $userData['role'] ?? UserRole::Member->value,
                        'password' => Hash::make(Str::random(32)),
                    ]
                );
                $imported++;
            }
        });

        $this->auditLogger->activity('data.imported', $user, null, ['imported_users' => $imported]);

        return response()->json([
            'message' => 'Import successful',
            'imported_users' => $imported,
        ]);
    }

    public function migrate(Request $request)
    {
        $this->authorize('data.migrate');

        $migration = $this->migrationService->queue($request->user());

        RunDataMigrationJob::dispatch($migration->id);

        $migration = $migration->fresh();

        return response()->json([
            'message' => $migration->status === 'completed' ? 'Migration completed' : 'Migration queued',
            'migration' => $migration,
        ], $migration->status === 'pending' ? 202 : 200);
    }

    public function migrationStatus(Request $request, int $migrationId)
    {
        $this->authorize('data.migrate');

        $migration = DataMigration::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($migrationId);

        return response()->json(['migration' => $migration]);
    }

    public function listMigrations(Request $request)
    {
        $this->authorize('data.migrate');

        return response()->json(
            DataMigration::query()->latest()->paginate($this->perPage($request))
        );
    }

    public function deleteTenantData(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('deleteData', $tenant);

        $this->auditLogger->audit('tenant.data_deleted', $request->user(), $tenant->id, Tenant::class, $tenant->id);

        $tenant->integrations()->delete();
        $tenant->users()->delete();

        return response()->json(['message' => 'Tenant data deleted']);
    }
}
