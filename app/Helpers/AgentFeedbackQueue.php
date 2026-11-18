<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Enums\AgentFeedbackStatusEnum;
use App\Models\AgentFeedbackReport;
use Illuminate\Database\Eloquent\Builder;

/**
 * The agent feedback reports as a query: one status, the filters over it, and the count of every
 * status under the same filters. The admin screen, the API and the MCP server all read the reports
 * through it, so they agree on what a filter means.
 */
class AgentFeedbackQueue
{
    public const string ALL = 'all';

    public readonly string $status;

    public readonly string $search;

    public readonly string $kind;

    public readonly string $severity;

    public function __construct(
        string $status = '',
        string $search = '',
        string $kind = '',
        string $severity = '',
    ) {
        $this->status = in_array($status, [...AgentFeedbackStatusEnum::values(), self::ALL], true) ? $status : AgentFeedbackStatusEnum::New->value;
        $this->search = mb_trim($search);
        $this->kind = in_array($kind, AgentFeedbackKindEnum::values(), true) ? $kind : '';
        $this->severity = in_array($severity, AgentFeedbackSeverityEnum::values(), true) ? $severity : '';
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->kind !== '' || $this->severity !== '';
    }

    /** @return array<string, string> */
    public function parameters(): array
    {
        return array_filter([
            'status' => $this->status,
            'q' => $this->search,
            'kind' => $this->kind,
            'severity' => $this->severity,
        ], fn (string $value): bool => $value !== '');
    }

    /** @return Builder<AgentFeedbackReport> */
    public function query(): Builder
    {
        return $this->filtered()
            ->when($this->status !== self::ALL, fn (Builder $query): Builder => $query->where('status', $this->status))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        $counts = [];

        foreach (AgentFeedbackStatusEnum::cases() as $status) {
            $counts[$status->value] = $this->filtered()->where('status', $status->value)->count();
        }

        $counts[self::ALL] = $this->filtered()->count();

        return $counts;
    }

    /** @return Builder<AgentFeedbackReport> */
    private function filtered(): Builder
    {
        $search = $this->search;

        return AgentFeedbackReport::query()
            ->when($this->kind !== '', fn (Builder $query): Builder => $query->where('kind', $this->kind))
            ->when($this->severity !== '', fn (Builder $query): Builder => $query->where('severity', $this->severity))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                ->whereLike('title', "%{$search}%")
                ->orWhereLike('tool', "%{$search}%")
                ->orWhereHas('user', fn (Builder $users): Builder => $users
                    ->whereLike('first_name', "%{$search}%")
                    ->orWhereLike('last_name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%"))));
    }
}
