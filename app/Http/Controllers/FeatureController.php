<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feature\OverrideFeatureRequest;
use App\Http\Requests\Feature\StoreFeatureRequest;
use App\Http\Requests\Feature\UpdateFeatureRequest;
use App\Models\Feature;
use App\Models\Tenant;
use App\Models\TenantFeatureOverride;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function index(Request $request)
    {
        return response()->json(
            Feature::query()->orderBy('key')->paginate($this->perPage($request))
        );
    }

    public function store(StoreFeatureRequest $request)
    {
        $this->authorize('create', Feature::class);

        $feature = Feature::create($request->validated());

        $this->auditLogger->audit('feature.created', $request->user(), null, Feature::class, $feature->id);

        return response()->json(['message' => 'Feature created', 'feature' => $feature], 201);
    }

    public function update(UpdateFeatureRequest $request, int $featureId)
    {
        $feature = Feature::findOrFail($featureId);
        $this->authorize('update', $feature);

        $feature->update($request->validated());

        $this->auditLogger->audit('feature.updated', $request->user(), null, Feature::class, $feature->id);

        return response()->json(['message' => 'Feature updated', 'feature' => $feature->fresh()]);
    }

    public function tenantFeatures(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('viewFeatures', $tenant);

        $overrides = TenantFeatureOverride::where('tenant_id', $tenantId)->get()->keyBy('feature_key');

        $paginator = Feature::query()
            ->orderBy('key')
            ->paginate($this->perPage($request));

        $paginator->getCollection()->transform(function (Feature $feature) use ($overrides) {
            $override = $overrides->get($feature->key);

            return [
                'key' => $feature->key,
                'name' => $feature->name,
                'description' => $feature->description,
                'enabled' => $override && $override->enabled !== null
                    ? $override->enabled
                    : $feature->default_enabled,
                'limit' => $override && $override->limit !== null
                    ? $override->limit
                    : $feature->default_limit,
            ];
        });

        return response()->json($paginator);
    }

    public function override(OverrideFeatureRequest $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('overrideFeatures', $tenant);

        $override = TenantFeatureOverride::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'feature_key' => $request->feature_key,
            ],
            [
                'enabled' => $request->enabled,
                'limit' => $request->limit,
            ]
        );

        $this->auditLogger->activity('feature.override', $request->user(), $override);

        return response()->json([
            'message' => 'Feature override saved',
            'override' => $override,
        ]);
    }
}
