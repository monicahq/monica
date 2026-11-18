<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Enums\AgentFeedbackStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AgentFeedbackReport;
use App\Models\User;

/**
 * Record a problem an AI agent met with the MCP server, for the administrators of the instance. The
 * report starts as new, and the person the agent was working for sees only a line in their log.
 */
class SubmitAgentFeedback
{
    private AgentFeedbackReport $report;

    /** @param  list<array{tool: string, arguments?: string|null, outcome: string, result?: string|null}>  $steps */
    public function __construct(
        private readonly User $user,
        private readonly string $connectionName,
        private readonly AgentFeedbackKindEnum $kind,
        private readonly AgentFeedbackSeverityEnum $severity,
        private readonly string $title,
        private readonly string $observed,
        private readonly string $expected,
        private readonly string $proposedFix,
        private readonly ?string $tool = null,
        private readonly ?string $request = null,
        private readonly array $steps = [],
        private readonly ?string $proposedCode = null,
        private readonly ?string $model = null,
    ) {}

    public function execute(): AgentFeedbackReport
    {
        $this->create();
        $this->log();

        return $this->report;
    }

    private function create(): void
    {
        $this->report = AgentFeedbackReport::query()->create([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'status' => AgentFeedbackStatusEnum::New,
            'kind' => $this->kind,
            'severity' => $this->severity,
            'title' => mb_trim($this->title),
            'tool' => $this->tool,
            'request' => $this->request,
            'steps' => $this->steps === [] ? null : array_map(fn (array $step): array => [
                'tool' => $step['tool'],
                'arguments' => $step['arguments'] ?? null,
                'outcome' => $step['outcome'],
                'result' => $step['result'] ?? null,
            ], $this->steps),
            'observed' => mb_trim($this->observed),
            'expected' => mb_trim($this->expected),
            'proposed_fix' => mb_trim($this->proposedFix),
            'proposed_code' => $this->proposedCode,
            'model' => $this->model,
            'connection_name' => $this->connectionName,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::AgentFeedbackSubmitted,
            parameters: ['title' => $this->report->title],
        );
    }
}
