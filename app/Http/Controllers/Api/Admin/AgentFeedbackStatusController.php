<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ChangeAgentFeedbackStatus;
use App\Enums\AgentFeedbackStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgentFeedbackReportResource;
use App\Models\AgentFeedbackReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentFeedbackStatusController extends Controller
{
    public function update(Request $request, string $agentFeedbackReport): JsonResponse
    {
        $report = AgentFeedbackReport::query()->where('uuid', $agentFeedbackReport)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', Rule::enum(AgentFeedbackStatusEnum::class)],
        ]);

        $report = new ChangeAgentFeedbackStatus(
            administrator: $request->user(),
            report: $report,
            status: AgentFeedbackStatusEnum::from($validated['status']),
        )->execute();

        return new AgentFeedbackReportResource($report->load('user'))->response()->setStatusCode(200);
    }
}
