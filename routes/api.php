use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\DataController;
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\IamController;
use App\Http\Controllers\ObservabilityController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\DataController;
Route::get('/health', [ObservabilityController::class, 'health']);
Route::get('/status', [ObservabilityController::class, 'status']);
Route::get('/metrics', [ObservabilityController::class, 'metrics']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/sso', [AuthController::class, 'sso']);
Route::middleware('auth:sanctum')->group(function () {
	// Admin / Super-Admin APIs
	Route::get('/admin/tenants', [AdminController::class, 'listTenants']);
	Route::get('/admin/tenants/{tenantId}/usage', [AdminController::class, 'tenantUsage']);
	Route::post('/admin/tenants/{tenantId}/suspend', [AdminController::class, 'suspendTenant']);
	Route::post('/admin/impersonate-user', [AdminController::class, 'impersonateUser']);
	// Audit, Logs & Compliance APIs
	Route::get('/audit-logs', [AuditController::class, 'getAuditLogs']);
	Route::get('/tenants/{tenantId}/audit-logs', [AuditController::class, 'getTenantAuditLogs']);
	Route::get('/activity-logs', [AuditController::class, 'getActivityLogs']);
	Route::post('/compliance/gdpr/export', [AuditController::class, 'gdprExport']);
	Route::post('/compliance/gdpr/delete', [AuditController::class, 'gdprDelete']);
	// Data Isolation & Storage APIs
	Route::get('/data/export', [DataController::class, 'export']);
	Route::post('/data/import', [DataController::class, 'import']);
	Route::post('/data/migrate', [DataController::class, 'migrate']);
	Route::delete('/tenants/{tenantId}/data', [DataController::class, 'deleteTenantData']);
	Route::post('/auth/logout', [AuthController::class, 'logout']);
	Route::post('/auth/refresh', [AuthController::class, 'refresh']);
	Route::get('/tenants/{tenantId}/metrics', [ObservabilityController::class, 'tenantMetrics']);
	Route::post('/tenants/{tenantId}/users', [TenantController::class, 'createTenantUser']);
	Route::get('/tenants/{tenantId}/users', [TenantController::class, 'listTenantUsers']);
	Route::put('/users/{userId}', [TenantController::class, 'updateUser']);
	Route::delete('/users/{userId}', [TenantController::class, 'deleteUser']);
	Route::get('/tenant/current', [TenantController::class, 'current']);
	Route::put('/tenant/update', [TenantController::class, 'update']);
	Route::post('/tenant/invite-user', [TenantController::class, 'inviteUser']);
	Route::get('/tenant/users', [TenantController::class, 'getUsers']);
	Route::post('/tenant/remove-user', [TenantController::class, 'removeUser']);
	Route::post('/roles', [IamController::class, 'createRole']);
	Route::get('/roles', [IamController::class, 'listRoles']);
	Route::post('/permissions', [IamController::class, 'createPermission']);
	Route::get('/users/{userId}/permissions', [IamController::class, 'getUserPermissions']);
	Route::get('/features', [FeatureController::class, 'index']);
	Route::get('/tenants/{tenantId}/features', [FeatureController::class, 'tenantFeatures']);
	Route::post('/tenants/{tenantId}/features/override', [FeatureController::class, 'override']);
	Route::get('/usage', [UsageController::class, 'index']);
	Route::post('/usage/report', [UsageController::class, 'report']);
	Route::get('/user/profile', function (Request $request) {
		return response()->json($request->user());
	});
});
