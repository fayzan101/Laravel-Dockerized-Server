<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\CreateTenantRequest;
use App\Http\Requests\Admin\CrossTenantMigrationRequest;
use App\Http\Requests\Admin\ImpersonateUserRequest;
use App\Http\Requests\Admin\UpdateAdminTenantRequest;
use App\Http\Requests\Admin\UpdatePlatformSettingsRequest;
use App\Enums\UserRole;
use App\Jobs\RunDataMigrationJob;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\GdprRequest;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ComplianceReportService;
use App\Services\DataMigrationService;
use App\Services\TenantUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private TenantUsageService $usageService,
        private ComplianceReportService $complianceReport,
        private DataMigrationService $migrationService
    ) {
    }

    public function listTenants(Request $request)
    {
        $this->authorize('admin.list-tenants');

        return response()->json(
            Tenant::query()->withCount('users')->latest()->paginate($this->perPage($request))
        );
    }

    public function createTenant(CreateTenantRequest $request)
    {
        $this->authorize('admin.create-tenant');

        $tenant = Tenant::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'domain' => $request->domain,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $owner = User::create([
            'name' => $request->owner_name,
            'email' => $request->owner_email,
            'password' => Hash::make($request->owner_password),
            'tenant_id' => $tenant->id,
            'role' => UserRole::Admin->value,
        ]);

        $tenant->update(['owner_id' => $owner->id]);

        $this->auditLogger->audit('tenant.created', $request->user(), $tenant->id, Tenant::class, $tenant->id);

        return response()->json([
            'message' => 'Tenant created',
            'tenant' => $tenant->load('owner'),
        ], 201);
    }

    public function showTenant(Request $request, int $tenantId)
    {
        $tenant = Tenant::withCount('users')->findOrFail($tenantId);
        $this->authorize('admin.view-tenant', $tenant);

        return response()->json($tenant);
    }

    public function updateTenant(UpdateAdminTenantRequest $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('admin.update-tenant', $tenant);

        $tenant->update($request->only(['name', 'slug', 'description', 'domain', 'status', 'settings']));

        $this->auditLogger->audit('tenant.updated', $request->user(), $tenant->id, Tenant::class, $tenant->id);

        return response()->json(['message' => 'Tenant updated', 'tenant' => $tenant->fresh()]);
    }

    public function deleteTenant(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('admin.delete-tenant', $tenant);

        $this->auditLogger->audit('tenant.deleted', $request->user(), $tenant->id, Tenant::class, $tenant->id);

        $tenant->delete();

        return response()->json(['message' => 'Tenant soft-deleted']);
    }

    public function tenantUsage(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('admin.view-tenant-usage', $tenant);

        return response()->json($this->usageService->metricsFor($tenant));
    }

    public function suspendTenant(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('admin.suspend-tenant', $tenant);

        $tenant->update(['status' => 'suspended']);

        $this->auditLogger->audit('tenant.suspended', $request->user(), $tenant->id, Tenant::class, $tenant->id);

        return response()->json(['message' => 'Tenant suspended', 'tenant' => $tenant->fresh()]);
    }

    public function reactivateTenant(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('admin.reactivate-tenant', $tenant);

        $tenant->activate();

        $this->auditLogger->audit('tenant.reactivated', $request->user(), $tenant->id, Tenant::class, $tenant->id);

        return response()->json(['message' => 'Tenant reactivated', 'tenant' => $tenant->fresh()]);
    }

    public function impersonateUser(ImpersonateUserRequest $request)
    {
        $this->authorize('admin.impersonate');

        $target = User::findOrFail($request->user_id);
        $token = $target->createToken('impersonation')->plainTextToken;

        $this->auditLogger->audit('user.impersonated', $request->user(), $target->tenant_id, User::class, $target->id, [
            'target_user_id' => $target->id,
        ]);

        return response()->json([
            'message' => 'Impersonation started',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $target,
        ]);
    }

    public function dashboard(Request $request)
    {
        $this->authorize('admin.dashboard');

        return response()->json([
            'platform' => $this->complianceReport->generate(),
            'recent_audit_logs' => AuditLog::latest()->limit(10)->get(),
            'recent_activity' => ActivityLog::latest()->limit(10)->get(),
            'tenants_by_status' => Tenant::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'gdpr_requests_pending' => GdprRequest::where('status', 'processing')->count(),
        ]);
    }

    public function getSettings(Request $request)
    {
        $this->authorize('admin.settings.view');

        return response()->json([
            'settings' => PlatformSetting::get('platform', [
                'maintenance_mode' => false,
                'default_tenant_status' => 'active',
                'max_users_per_tenant' => 100,
                'support_email' => 'support@example.com',
            ]),
        ]);
    }

    public function updateSettings(UpdatePlatformSettingsRequest $request)
    {
        $this->authorize('admin.settings.update');

        PlatformSetting::set('platform', $request->input('settings'));

        $this->auditLogger->audit('platform.settings_updated', $request->user());

        return response()->json([
            'message' => 'Platform settings updated',
            'settings' => PlatformSetting::get('platform'),
        ]);
    }

    public function crossTenantMigrate(CrossTenantMigrationRequest $request)
    {
        $this->authorize('admin.cross-tenant-migrate');

        $migration = $this->migrationService->queue(
            $request->user(),
            'cross_tenant',
            $request->source_tenant_id,
            $request->target_tenant_id
        );

        RunDataMigrationJob::dispatch($migration->id);

        return response()->json([
            'message' => 'Cross-tenant migration queued',
            'migration' => $migration,
        ], 202);
    }
}
