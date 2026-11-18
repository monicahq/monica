<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\AgentFeedbackStatusEnum;
use App\Models\AgentFeedbackNote;
use App\Models\AgentFeedbackReport;
use App\Models\User;

class AgentFeedbackReportViewModel
{
    public function __construct(
        private readonly User $administrator,
        private readonly AgentFeedbackReport $report,
    ) {}

    /** @return array{uuid: string, number: string, title: string, statusLabel: string, statusTone: string, severityLabel: string, severityTone: string, kindLabel: string, byline: string, tool: string|null, request: string|null, observed: string, expected: string, proposedFix: string, proposedCode: string|null} */
    public function report(): array
    {
        $report = $this->report;

        return [
            'uuid' => $report->uuid,
            'number' => "#{$report->id}",
            'title' => $report->title,
            'statusLabel' => __($report->status->label()),
            'statusTone' => $report->status->tone(),
            'severityLabel' => __($report->severity->label()),
            'severityTone' => $report->severity->tone(),
            'kindLabel' => __($report->kind->label()),
            'byline' => $report->model === null
                ? __('Sent through :connection on :date', ['connection' => $this->connection(), 'date' => $this->submittedAt()])
                : __('Sent by :model through :connection on :date', ['model' => $report->model, 'connection' => $this->connection(), 'date' => $this->submittedAt()]),
            'tool' => $report->tool,
            'request' => $report->request,
            'observed' => $report->observed,
            'expected' => $report->expected,
            'proposedFix' => $report->proposed_fix,
            'proposedCode' => $report->proposed_code,
        ];
    }

    /** @return list<array{number: int, tool: string, arguments: string|null, succeeded: bool, result: string|null}> */
    public function steps(): array
    {
        return array_values(array_map(fn (array $step, int $index): array => [
            'number' => $index + 1,
            'tool' => $step['tool'],
            'arguments' => $step['arguments'] ?? null,
            'succeeded' => $step['outcome'] === 'success',
            'result' => $step['result'] ?? null,
        ], $this->report->steps ?? [], array_keys($this->report->steps ?? [])));
    }

    /** @return list<array{value: string, label: string, primary: bool}> */
    public function transitions(): array
    {
        return array_map(fn (AgentFeedbackStatusEnum $status): array => [
            'value' => $status->value,
            'label' => match ($status) {
                AgentFeedbackStatusEnum::New => __('Reopen'),
                AgentFeedbackStatusEnum::Accepted => __('Accept'),
                AgentFeedbackStatusEnum::Fixed => __('Mark as fixed'),
                AgentFeedbackStatusEnum::Dismissed => __('Dismiss'),
            },
            'primary' => in_array($status, [AgentFeedbackStatusEnum::Accepted, AgentFeedbackStatusEnum::Fixed], true),
        ], $this->report->status->allowedTargets());
    }

    /** @return array{name: string, email: string, accountUrl: string} */
    public function person(): array
    {
        return [
            'name' => $this->report->user->name ?? __('Deleted user'),
            'email' => $this->report->user->email ?? '',
            'accountUrl' => route('admin.accounts.show', ['account' => $this->report->account_id]),
        ];
    }

    /** @return list<array{label: string, value: string}> */
    public function session(): array
    {
        return array_values(array_filter([
            ['label' => __('Connection'), 'value' => $this->connection()],
            ['label' => __('Model'), 'value' => $this->report->model ?? ''],
            ['label' => __('Submitted'), 'value' => $this->submittedAt()],
            ['label' => __('Report ID'), 'value' => $this->report->uuid],
        ], fn (array $fact): bool => $fact['value'] !== ''));
    }

    /** @return list<array{author: string, when: string, body: string}> */
    public function notes(): array
    {
        return $this->report->notes()
            ->get()
            ->map(fn (AgentFeedbackNote $note): array => [
                'author' => $note->author_name,
                'when' => $note->created_at->setTimezone($this->administrator->timezone)->isoFormat('LLL'),
                'body' => $note->body,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{url: string, title: string, meta: string}> */
    public function sameTool(): array
    {
        if ($this->report->tool === null) {
            return [];
        }

        return AgentFeedbackReport::query()
            ->where('tool', $this->report->tool)
            ->whereKeyNot($this->report->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (AgentFeedbackReport $report): array => [
                'url' => route('admin.agentFeedback.show', ['agentFeedbackReport' => $report->uuid]),
                'title' => $report->title,
                'meta' => __($report->status->label()).' · '.$report->created_at->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    public function markdown(): string
    {
        $report = $this->report;

        $lines = [
            "## {$report->title}",
            '',
            ...($report->tool === null ? [] : ["Tool: `{$report->tool}`", '']),
            '### Observed',
            $report->observed,
            '',
            '### Expected',
            $report->expected,
            '',
            '### Proposed fix',
            $report->proposed_fix,
            ...($report->proposed_code === null ? [] : ['', '```', $report->proposed_code, '```']),
        ];

        return implode("\n", $lines);
    }

    private function connection(): string
    {
        return $this->report->connection_name === '' ? __('an unnamed connection') : $this->report->connection_name;
    }

    private function submittedAt(): string
    {
        return $this->report->created_at->setTimezone($this->administrator->timezone)->isoFormat('LLL');
    }
}
