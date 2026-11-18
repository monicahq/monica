<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Helpers\AgentFeedbackQueue;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\AgentFeedbackReport;
use App\ViewModels\Admin\AgentFeedbackQueueViewModel;
use App\ViewModels\Admin\AgentFeedbackReportViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.agent-feedback.index', [
            'viewModel' => new AgentFeedbackQueueViewModel(
                queue: new AgentFeedbackQueue(
                    status: (string) $request->string('status'),
                    search: (string) $request->string('q'),
                    kind: (string) $request->string('kind'),
                    severity: (string) $request->string('severity'),
                ),
                size: PageSize::from($request, AgentFeedbackQueueViewModel::PER_PAGE),
            ),
        ]);
    }

    public function show(Request $request, string $agentFeedbackReport): View
    {
        return view('app.admin.agent-feedback.show', [
            'viewModel' => new AgentFeedbackReportViewModel(
                administrator: $request->user(),
                report: AgentFeedbackReport::query()->where('uuid', $agentFeedbackReport)->with('user')->firstOrFail(),
            ),
        ]);
    }
}
