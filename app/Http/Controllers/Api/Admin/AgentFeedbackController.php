<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\AgentFeedbackQueue;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgentFeedbackReportResource;
use App\Models\AgentFeedbackReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $queue = new AgentFeedbackQueue(
            status: (string) $request->string('status'),
            search: (string) $request->string('q'),
            kind: (string) $request->string('kind'),
            severity: (string) $request->string('severity'),
        );

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $reports = $queue->query()->with('user')->paginate($perPage);

        return response()->json([
            'data' => array_map(
                fn (AgentFeedbackReport $report): array => new AgentFeedbackReportResource($report)->resolve($request),
                $reports->items(),
            ),
            'meta' => [
                'page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'counts' => $queue->counts(),
            ],
        ]);
    }

    public function show(Request $request, string $agentFeedbackReport): JsonResponse
    {
        $report = AgentFeedbackReport::query()
            ->where('uuid', $agentFeedbackReport)
            ->with(['user', 'notes'])
            ->firstOrFail();

        return new AgentFeedbackReportResource($report)->response()->setStatusCode(200);
    }
}
