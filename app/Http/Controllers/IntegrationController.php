<?php

namespace App\Http\Controllers;

use App\Http\Requests\Integration\StoreIntegrationRequest;
use App\Http\Requests\Integration\UpdateIntegrationRequest;
use App\Models\Integration;
use App\Models\Tenant;
use App\Services\AuditLogger;
use App\Services\PlatformLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IntegrationController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PlatformLimitService $limits
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Integration::class);

        return response()->json(
            Integration::query()->latest()->paginate($this->perPage($request))
        );
    }

    public function store(StoreIntegrationRequest $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('manageIntegrations', $tenant);

        $this->limits->assertCanAddIntegration($tenantId);

        $integration = $tenant->integrations()->create($request->validated());

        $this->auditLogger->activity('integration.created', $request->user(), $integration);

        return response()->json(['integration' => $integration], 201);
    }

    public function show(Request $request, int $integrationId)
    {
        $integration = Integration::findOrFail($integrationId);
        $this->authorize('view', $integration);

        return response()->json(['integration' => $integration]);
    }

    public function update(UpdateIntegrationRequest $request, int $integrationId)
    {
        $integration = Integration::findOrFail($integrationId);
        $this->authorize('update', $integration);

        $integration->update($request->validated());

        $this->auditLogger->activity('integration.updated', $request->user(), $integration);

        return response()->json([
            'message' => 'Integration updated',
            'integration' => $integration->fresh(),
        ]);
    }

    public function destroy(Request $request, int $integrationId)
    {
        $integration = Integration::findOrFail($integrationId);
        $this->authorize('delete', $integration);

        $integration->delete();

        $this->auditLogger->activity('integration.deleted', $request->user(), $integration);

        return response()->json(['message' => 'Integration deleted']);
    }

    public function test(Request $request, int $integrationId)
    {
        $integration = Integration::findOrFail($integrationId);
        $this->authorize('test', $integration);

        if ($integration->type !== 'webhook') {
            return response()->json([
                'success' => false,
                'message' => 'Test connection is only supported for webhook integrations',
            ], 422);
        }

        $url = $integration->config['url'] ?? null;

        if (! $url) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook URL is missing from config',
            ], 422);
        }

        try {
            $response = Http::timeout(10)->post($url, [
                'event' => 'integration.test',
                'tenant_id' => $integration->tenant_id,
                'integration_id' => $integration->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message' => $response->successful()
                    ? 'Webhook responded successfully'
                    : 'Webhook returned an error status',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook request failed: ' . $e->getMessage(),
            ], 422);
        }
    }
}
