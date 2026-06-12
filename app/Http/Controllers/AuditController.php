<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\GdprRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuditRetentionService;
use App\Services\ComplianceReportService;
use App\Services\ExportStorageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private AuditRetentionService $retention,
        private ComplianceReportService $complianceReport,
        private ExportStorageService $exports
    ) {
    }

    public function getAuditLogs(Request $request)
    {
        $this->authorize('compliance.view-audit-logs');

        $user = $request->user();
        $query = AuditLog::query()->latest();

        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $this->applyAuditFilters($query, $request);

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function getTenantAuditLogs(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('viewAuditLogs', $tenant);

        $query = AuditLog::where('tenant_id', $tenantId)->latest();
        $this->applyAuditFilters($query, $request);

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function getActivityLogs(Request $request)
    {
        $this->authorize('compliance.view-activity-logs');

        $query = ActivityLog::where('tenant_id', $request->user()->tenant_id)->latest();

        if ($request->filled('activity')) {
            $query->where('activity', 'like', '%' . $request->query('activity') . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->query('to'));
        }

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function listGdprRequests(Request $request)
    {
        $this->authorize('compliance.gdpr-export');

        $query = GdprRequest::where('user_id', $request->user()->id)->latest();

        if ($request->user()->isSuperAdmin() && $request->filled('tenant_id')) {
            $query = GdprRequest::where('tenant_id', $request->query('tenant_id'))->latest();
        }

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function gdprExport(Request $request)
    {
        $this->authorize('compliance.gdpr-export');

        $user = $request->user();

        $gdprRequest = GdprRequest::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'type' => 'export',
            'status' => 'processing',
        ]);

        $exportData = [
            'exported_at' => now()->toIso8601String(),
            'user' => $user->only(['id', 'name', 'email', 'role', 'created_at']),
            'tenant' => $user->tenant?->only(['id', 'name', 'slug', 'status']),
            'activity_logs' => ActivityLog::where('user_id', $user->id)->latest()->limit(100)->get(),
        ];

        $path = 'gdpr/exports/user-' . $user->id . '-' . now()->timestamp . '.json';
        $this->exports->put($path, json_encode($exportData, JSON_PRETTY_PRINT));

        $gdprRequest->update([
            'status' => 'completed',
            'result' => [
                'path' => $path,
                'filename' => basename($path),
                'disk' => $this->exports->disk(),
            ],
        ]);

        $this->auditLogger->audit('gdpr.export', $user);

        return response()->json([
            'message' => 'GDPR export completed',
            'status' => 'completed',
            'request_id' => $gdprRequest->id,
            'download_url' => url('/api/compliance/gdpr/requests/' . $gdprRequest->id . '/download'),
        ]);
    }

    public function downloadGdprExport(Request $request, int $requestId): StreamedResponse
    {
        $this->authorize('compliance.gdpr-export');

        $gdprRequest = GdprRequest::where('user_id', $request->user()->id)
            ->where('type', 'export')
            ->where('status', 'completed')
            ->findOrFail($requestId);

        $path = $gdprRequest->result['path'] ?? null;
        $disk = $gdprRequest->result['disk'] ?? $this->exports->disk();

        if (! $path || ! \Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
            abort(404, 'Export file not found');
        }

        return \Illuminate\Support\Facades\Storage::disk($disk)->download(
            $path,
            $gdprRequest->result['filename'] ?? 'gdpr-export.json'
        );
    }

    public function gdprDelete(Request $request)
    {
        $this->authorize('compliance.gdpr-delete');

        $user = $request->user();

        $gdprRequest = GdprRequest::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'type' => 'delete',
            'status' => 'processing',
        ]);

        $this->auditLogger->audit('gdpr.delete', $user, $user->tenant_id, User::class, $user->id);

        $user->tokens()->delete();
        $user->delete();

        $gdprRequest->update(['status' => 'completed']);

        return response()->json([
            'message' => 'GDPR delete completed',
            'status' => 'completed',
            'request_id' => $gdprRequest->id,
        ]);
    }

    public function exportAuditLogs(Request $request): StreamedResponse
    {
        $this->authorize('compliance.export-audit-logs');

        $user = $request->user();
        $tenantId = $user->isSuperAdmin() && $request->filled('tenant_id')
            ? (int) $request->query('tenant_id')
            : $user->tenant_id;

        $payload = $this->retention->exportBulk($tenantId, $request->query('from'), $request->query('to'));

        $filename = 'audit-export-' . now()->timestamp . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function complianceReport(Request $request)
    {
        $this->authorize('compliance.report');

        $user = $request->user();
        $tenantId = $user->isSuperAdmin() && $request->filled('tenant_id')
            ? (int) $request->query('tenant_id')
            : $user->tenant_id;

        return response()->json($this->complianceReport->generate($tenantId));
    }

    public function archiveLogs(Request $request)
    {
        $this->authorize('compliance.archive-logs');

        $result = $this->retention->archiveExpired();

        $this->auditLogger->audit('audit.archived', $request->user(), null, null, null, $result);

        return response()->json([
            'message' => 'Audit logs archived',
            'result' => $result,
        ]);
    }

    private function applyAuditFilters($query, Request $request): void
    {
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->query('action') . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->query('to'));
        }
    }
}
