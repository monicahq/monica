<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Enums\AgentFeedbackStatusEnum;
use App\Helpers\AgentFeedbackQueue;
use App\Helpers\PageSize;
use App\Models\AgentFeedbackReport;
use Illuminate\Pagination\LengthAwarePaginator;

class AgentFeedbackQueueViewModel
{
    public const int PER_PAGE = 50;

    public function __construct(
        private readonly AgentFeedbackQueue $queue,
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    public function queue(): AgentFeedbackQueue
    {
        return $this->queue;
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    public function clearFiltersUrl(): string
    {
        return route('admin.agentFeedback.index', ['status' => $this->queue->status]);
    }

    /** @return list<array{key: string, label: string, count: int, url: string, selected: bool}> */
    public function statuses(): array
    {
        $counts = $this->queue->counts();
        $labels = [];

        foreach (AgentFeedbackStatusEnum::cases() as $status) {
            $labels[$status->value] = __($status->label());
        }

        $labels[AgentFeedbackQueue::ALL] = __('All');

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $labels[$key],
            'count' => $counts[$key],
            'url' => route('admin.agentFeedback.index', [...$this->queue->parameters(), 'status' => $key]),
            'selected' => $key === $this->queue->status,
        ], array_keys($labels));
    }

    /** @return list<array{value: string, label: string}> */
    public function kindOptions(): array
    {
        return [
            ['value' => '', 'label' => __('All kinds')],
            ...array_map(fn (AgentFeedbackKindEnum $kind): array => ['value' => $kind->value, 'label' => __($kind->label())], AgentFeedbackKindEnum::cases()),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function severityOptions(): array
    {
        return [
            ['value' => '', 'label' => __('Any severity')],
            ...array_map(fn (AgentFeedbackSeverityEnum $severity): array => ['value' => $severity->value, 'label' => __($severity->label())], AgentFeedbackSeverityEnum::cases()),
        ];
    }

    /** @return LengthAwarePaginator<int, AgentFeedbackReport> */
    public function reports(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            $this->queue->query()->with('user')->withCount('notes'),
            $this->size,
        )->withQueryString());
    }

    /** @return list<array{url: string, title: string, statusLabel: string, statusIcon: string, severityLabel: string, severityTone: string, kindLabel: string, meta: string, tool: string|null, noteCount: int}> */
    public function rows(): array
    {
        return collect($this->reports()->items())
            ->map(fn (AgentFeedbackReport $report): array => [
                'url' => route('admin.agentFeedback.show', ['agentFeedbackReport' => $report->uuid]),
                'title' => $report->title,
                'statusLabel' => __($report->status->label()),
                'statusIcon' => $report->status->icon(),
                'severityLabel' => __($report->severity->label()),
                'severityTone' => $report->severity->tone(),
                'kindLabel' => __($report->kind->label()),
                'meta' => implode(' · ', array_filter([
                    "#{$report->id}",
                    $report->created_at->diffForHumans(),
                    $report->model,
                    $report->user->name ?? __('Deleted user'),
                ])),
                'tool' => $report->tool,
                'noteCount' => (int) $report->notes_count,
            ])
            ->values()
            ->all();
    }

    public function emptyTitle(): string
    {
        if ($this->queue->hasFilters()) {
            return __('No report matches these filters');
        }

        return match ($this->queue->status) {
            AgentFeedbackStatusEnum::New->value => __('Nothing new'),
            AgentFeedbackStatusEnum::Accepted->value => __('Nothing accepted'),
            AgentFeedbackStatusEnum::Fixed->value => __('Nothing fixed yet'),
            AgentFeedbackStatusEnum::Dismissed->value => __('Nothing dismissed'),
            default => __('No reports yet'),
        };
    }

    public function emptyBody(): string
    {
        if ($this->queue->hasFilters()) {
            return __('Try another word, or clear the kind and severity filters.');
        }

        return match ($this->queue->status) {
            AgentFeedbackStatusEnum::New->value => __('When an agent hits a problem with a tool, its report lands here until somebody accepts or dismisses it.'),
            AgentFeedbackStatusEnum::Accepted->value => __('Reports you accept stay here until you mark them as fixed.'),
            AgentFeedbackStatusEnum::Fixed->value => __('Reports you mark as fixed land here.'),
            AgentFeedbackStatusEnum::Dismissed->value => __('Reports you dismiss land here, and you can reopen them.'),
            default => __('Reports agents send through submit-feedback land here.'),
        };
    }
}
