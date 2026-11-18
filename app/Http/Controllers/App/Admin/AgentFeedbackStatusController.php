<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ChangeAgentFeedbackStatus;
use App\Enums\AgentFeedbackStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\AgentFeedbackReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentFeedbackStatusController extends Controller
{
    public function update(Request $request, string $agentFeedbackReport): RedirectResponse
    {
        $report = AgentFeedbackReport::query()->where('uuid', $agentFeedbackReport)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', Rule::enum(AgentFeedbackStatusEnum::class)],
        ]);

        new ChangeAgentFeedbackStatus(
            administrator: $request->user(),
            report: $report,
            status: AgentFeedbackStatusEnum::from($validated['status']),
        )->execute();

        return redirect()->route('admin.agentFeedback.show', ['agentFeedbackReport' => $report->uuid])
            ->with('status', __('Status changed.'));
    }
}
