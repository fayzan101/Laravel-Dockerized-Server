<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\IamController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ObservabilityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UsageController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [ObservabilityController::class, 'health']);
Route::get('/status', [ObservabilityController::class, 'status']);
Route::get('/metrics', [ObservabilityController::class, 'metrics']);

Route::middleware('throttle:auth')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/sso', [AuthController::class, 'sso']);
});

Route::middleware(['auth:sanctum', 'tenant.active'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::put('/user/password', [ProfileController::class, 'changePassword']);

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/settings', [AdminController::class, 'getSettings']);
    Route::put('/admin/settings', [AdminController::class, 'updateSettings']);
    Route::post('/admin/tenants', [AdminController::class, 'createTenant']);
    Route::get('/admin/tenants/{tenantId}', [AdminController::class, 'showTenant']);
    Route::put('/admin/tenants/{tenantId}', [AdminController::class, 'updateTenant']);
    Route::delete('/admin/tenants/{tenantId}', [AdminController::class, 'deleteTenant']);
    Route::post('/admin/tenants/{tenantId}/suspend', [AdminController::class, 'suspendTenant']);
    Route::post('/admin/tenants/{tenantId}/reactivate', [AdminController::class, 'reactivateTenant']);
    Route::post('/admin/impersonate-user', [AdminController::class, 'impersonateUser']);
    Route::get('/admin/tenants/{tenantId}/usage', [AdminController::class, 'tenantUsage']);
    Route::post('/admin/data/migrate', [AdminController::class, 'crossTenantMigrate']);

    Route::post('/compliance/gdpr/export', [AuditController::class, 'gdprExport'])->middleware('permission:compliance.gdpr');
    Route::post('/compliance/gdpr/delete', [AuditController::class, 'gdprDelete'])->middleware('permission:compliance.gdpr');
    Route::get('/compliance/gdpr/requests/{requestId}/download', [AuditController::class, 'downloadGdprExport'])->middleware('permission:compliance.gdpr');
    Route::get('/compliance/audit/export', [AuditController::class, 'exportAuditLogs'])->middleware('permission:audit.view');
    Route::get('/compliance/report', [AuditController::class, 'complianceReport'])->middleware('permission:audit.view');
    Route::post('/compliance/audit/archive', [AuditController::class, 'archiveLogs']);

    Route::get('/data/export', [DataController::class, 'export'])->middleware('permission:data.export');
    Route::post('/data/import', [DataController::class, 'import'])->middleware('permission:data.import');
    Route::post('/data/migrate', [DataController::class, 'migrate'])->middleware('permission:data.migrate');
    Route::get('/data/migrations/{migrationId}', [DataController::class, 'migrationStatus'])->middleware('permission:data.migrate');
    Route::delete('/tenants/{tenantId}/data', [DataController::class, 'deleteTenantData'])->middleware('permission:data.delete');

    Route::get('/tenants/{tenantId}/metrics', [ObservabilityController::class, 'tenantMetrics']);
    Route::post('/tenants/{tenantId}/users', [TenantController::class, 'createTenantUser'])->middleware('permission:users.manage');
    Route::put('/users/{userId}', [TenantController::class, 'updateUser'])->middleware('permission:users.manage');
    Route::delete('/users/{userId}', [TenantController::class, 'deleteUser'])->middleware('permission:users.manage');

    Route::post('/tenants/{tenantId}/integrations', [IntegrationController::class, 'store'])->middleware('permission:integrations.manage');
    Route::get('/integrations/{integrationId}', [IntegrationController::class, 'show'])->middleware('permission:integrations.manage');
    Route::put('/integrations/{integrationId}', [IntegrationController::class, 'update'])->middleware('permission:integrations.manage');
    Route::delete('/integrations/{integrationId}', [IntegrationController::class, 'destroy'])->middleware('permission:integrations.manage');
    Route::post('/integrations/{integrationId}/test', [IntegrationController::class, 'test'])->middleware('permission:integrations.manage');

    Route::put('/tenant/update', [TenantController::class, 'update'])->middleware('permission:tenant.update');
    Route::post('/tenant/invite-user', [TenantController::class, 'inviteUser'])->middleware('permission:users.invite');
    Route::post('/tenant/remove-user', [TenantController::class, 'removeUser'])->middleware('permission:users.remove');
    Route::post('/tenant/transfer-ownership', [TenantController::class, 'transferOwnership'])->middleware('permission:tenant.update');
    Route::delete('/tenant', [TenantController::class, 'destroy'])->middleware('permission:tenant.update');
    Route::get('/tenant/current', [TenantController::class, 'current']);

    Route::post('/roles', [IamController::class, 'createRole'])->middleware('permission:roles.manage');
    Route::put('/roles/{roleId}', [IamController::class, 'updateRole'])->middleware('permission:roles.manage');
    Route::delete('/roles/{roleId}', [IamController::class, 'deleteRole'])->middleware('permission:roles.manage');
    Route::put('/roles/{roleId}/permissions', [IamController::class, 'syncRolePermissions'])->middleware('permission:roles.manage');
    Route::post('/permissions', [IamController::class, 'createPermission'])->middleware('permission:roles.manage');
    Route::put('/permissions/{permissionId}', [IamController::class, 'updatePermission'])->middleware('permission:roles.manage');
    Route::delete('/permissions/{permissionId}', [IamController::class, 'deletePermission'])->middleware('permission:roles.manage');
    Route::put('/users/{userId}/roles', [IamController::class, 'assignUserRoles'])->middleware('permission:users.manage');
    Route::put('/users/{userId}/system-role', [IamController::class, 'updateSystemRole'])->middleware('permission:users.manage');
    Route::get('/users/{userId}/permissions', [IamController::class, 'getUserPermissions'])->middleware('permission:roles.view');

    Route::post('/features', [FeatureController::class, 'store']);
    Route::put('/features/{featureId}', [FeatureController::class, 'update']);
    Route::post('/tenants/{tenantId}/features/override', [FeatureController::class, 'override'])->middleware('permission:features.override');
    Route::post('/usage/report', [UsageController::class, 'report'])->middleware('permission:usage.report');

    Route::middleware('throttle:api-lists')->group(function () {
        Route::get('/admin/tenants', [AdminController::class, 'listTenants']);

        Route::get('/audit-logs', [AuditController::class, 'getAuditLogs'])->middleware('permission:audit.view');
        Route::get('/tenants/{tenantId}/audit-logs', [AuditController::class, 'getTenantAuditLogs'])->middleware('permission:audit.view');
        Route::get('/activity-logs', [AuditController::class, 'getActivityLogs']);
        Route::get('/compliance/gdpr/requests', [AuditController::class, 'listGdprRequests'])->middleware('permission:compliance.gdpr');

        Route::get('/data/migrations', [DataController::class, 'listMigrations'])->middleware('permission:data.migrate');

        Route::get('/tenants/{tenantId}/users', [TenantController::class, 'listTenantUsers'])->middleware('permission:users.manage');
        Route::get('/tenant/users', [TenantController::class, 'getUsers']);

        Route::get('/integrations', [IntegrationController::class, 'index'])->middleware('permission:integrations.manage');

        Route::get('/roles', [IamController::class, 'listRoles'])->middleware('permission:roles.view');
        Route::get('/permissions', [IamController::class, 'listPermissions'])->middleware('permission:roles.view');

        Route::get('/features', [FeatureController::class, 'index']);
        Route::get('/tenants/{tenantId}/features', [FeatureController::class, 'tenantFeatures']);

        Route::get('/usage', [UsageController::class, 'index'])->middleware('permission:usage.view');
    });
});
