<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usage\ReportUsageRequest;
use App\Models\UsageRecord;
use App\Services\FeatureLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UsageController extends Controller
{
    public function __construct(private FeatureLimitService $featureLimits)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('usage.view');

        $actor = $request->user();
        $query = UsageRecord::where('tenant_id', $actor->tenant_id);

        if ($request->filled('feature_key')) {
            $query->where('feature_key', $request->query('feature_key'));
        }

        if ($request->filled('from')) {
            $query->where('recorded_at', '>=', Carbon::parse($request->query('from')));
        }

        if ($request->filled('to')) {
            $query->where('recorded_at', '<=', Carbon::parse($request->query('to')));
        }

        $summaryQuery = clone $query;
        $allRecords = $summaryQuery->get();
        $summary = $allRecords->groupBy('feature_key')->map(fn ($items) => $items->sum('amount'));

        $records = $query->latest('recorded_at')->paginate($this->perPage($request));

        return response()->json([
            'tenant_id' => $actor->tenant_id,
            'summary' => $summary,
            'records_count' => $allRecords->count(),
            'records' => $records,
        ]);
    }

    public function report(ReportUsageRequest $request)
    {
        $this->authorize('usage.report');

        $actor = $request->user();

        $this->featureLimits->assertCanReport($actor, $request->feature_key, (int) $request->amount);

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
