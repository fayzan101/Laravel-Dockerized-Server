<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\IamController;
use App\Http\Controllers\ObservabilityController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UsageController;
Route::get('/health', [ObservabilityController::class, 'health']);
Route::get('/status', [ObservabilityController::class, 'status']);
Route::get('/metrics', [ObservabilityController::class, 'metrics']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/sso', [AuthController::class, 'sso']);
Route::middleware('auth:sanctum')->group(function () {
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
