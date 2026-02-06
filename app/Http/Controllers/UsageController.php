<?php

namespace App\Http\Controllers;

use App\Models\UsageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UsageController extends Controller
{
    /**
     * Get usage summary for the current tenant.
     */
    public function index(Request $request)
    {
        $actor = $request->user();

        if (!$actor) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = UsageRecord::where('tenant_id', $actor->tenant_id);

        if ($request->filled('feature_key')) {
            $query->where('feature_key', $request->query('feature_key'));
        }

        if ($request->filled('from')) {
            $from = Carbon::parse($request->query('from'));
            $query->where('recorded_at', '>=', $from);
        }

        if ($request->filled('to')) {
            $to = Carbon::parse($request->query('to'));
            $query->where('recorded_at', '<=', $to);
        }

        $records = $query->get();

        $summary = $records->groupBy('feature_key')->map(function ($items) {
            return $items->sum('amount');
        });

        return response()->json([
            'tenant_id' => $actor->tenant_id,
            'summary' => $summary,
            'records_count' => $records->count(),
        ]);
    }

    /**
     * Report usage for the current tenant.
     */
    public function report(Request $request)
    {
        $actor = $request->user();

        if (!$actor || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'feature_key' => 'required|string',
            'amount' => 'required|integer|min:1',
            'metadata' => 'nullable|array',
            'recorded_at' => 'nullable|date',
        ]);

        $record = UsageRecord::create([
            'tenant_id' => $actor->tenant_id,
            'feature_key' => $request->feature_key,
            'amount' => $request->amount,
            'metadata' => $request->metadata,
            'recorded_at' => $request->recorded_at ? Carbon::parse($request->recorded_at) : now(),
        ]);

        return response()->json([
            'message' => 'Usage recorded',
            'record' => $record,
        ], 201);
    }
}
