<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\TenantFeatureOverride;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
        public function index()
    {
        return response()->json(Feature::all());
    }

        public function tenantFeatures(Request $request, int $tenantId)
    {
        $actor = $request->user();

        if (!$actor || $actor->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $features = Feature::all();
        $overrides = TenantFeatureOverride::where('tenant_id', $tenantId)->get()->keyBy('feature_key');

        $result = $features->map(function (Feature $feature) use ($overrides) {
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

        return response()->json($result);
    }

        public function override(Request $request, int $tenantId)
    {
        $actor = $request->user();

        if (!$actor || $actor->tenant_id !== $tenantId || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'feature_key' => 'required|string|exists:features,key',
            'enabled' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:0',
        ]);

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

        return response()->json([
            'message' => 'Feature override saved',
            'override' => $override,
        ]);
    }
}
