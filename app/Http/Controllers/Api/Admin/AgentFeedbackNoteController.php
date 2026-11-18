<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\AddAgentFeedbackNote;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgentFeedbackReportResource;
use App\Models\AgentFeedbackReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentFeedbackNoteController extends Controller
{
    public function create(Request $request, string $agentFeedbackReport): JsonResponse
    {
        $report = AgentFeedbackReport::query()->where('uuid', $agentFeedbackReport)->firstOrFail();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        new AddAgentFeedbackNote(
            administrator: $request->user(),
            report: $report,
            body: $validated['body'],
        )->execute();

        return new AgentFeedbackReportResource($report->load(['user', 'notes']))->response()->setStatusCode(201);
    }
}
