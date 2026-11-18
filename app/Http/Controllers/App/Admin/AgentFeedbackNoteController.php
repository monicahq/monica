<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\AddAgentFeedbackNote;
use App\Http\Controllers\Controller;
use App\Models\AgentFeedbackReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgentFeedbackNoteController extends Controller
{
    public function create(Request $request, string $agentFeedbackReport): RedirectResponse
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

        return redirect()->route('admin.agentFeedback.show', ['agentFeedbackReport' => $report->uuid])
            ->with('status', __('Note added.'));
    }
}
