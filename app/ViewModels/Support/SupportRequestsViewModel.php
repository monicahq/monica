<?php

declare(strict_types=1);

namespace App\ViewModels\Support;

use App\Enums\SupportRequestStatusEnum;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SupportRequestsViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly bool $showSolved = false,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function navigation(): SupportNavigation
    {
        return new SupportNavigation($this->user);
    }

    public function showsSolved(): bool
    {
        return $this->showSolved;
    }

    public function openCount(): int
    {
        return $this->navigation()->openRequestCount();
    }

    public function solvedCount(): int
    {
        return $this->query()->where('status', SupportRequestStatusEnum::Solved->value)->count();
    }

    /** @return list<array{url: string, reference: string, subject: string, categoryLabel: string, categoryIcon: string, updated: string, statusLabel: string, statusTone: string, statusIcon: string}> */
    public function rows(): array
    {
        return $this->query()
            ->when(
                $this->showSolved,
                fn (Builder $query): Builder => $query->where('status', SupportRequestStatusEnum::Solved->value),
                fn (Builder $query): Builder => $query->where('status', '!=', SupportRequestStatusEnum::Solved->value),
            )
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SupportRequest $supportRequest): array => [
                'url' => route('support.requests.show', $supportRequest),
                'reference' => $supportRequest->reference,
                'subject' => $supportRequest->subject,
                'categoryLabel' => __($supportRequest->category->shortLabel()),
                'categoryIcon' => $supportRequest->category->icon(),
                'updated' => __('Updated :when', ['when' => $supportRequest->last_activity_at->diffForHumans()]),
                'statusLabel' => __($supportRequest->status->userLabel()),
                'statusTone' => $supportRequest->status->userTone(),
                'statusIcon' => $supportRequest->status->icon(),
            ])
            ->values()
            ->all();
    }

    /** @return Builder<SupportRequest> */
    private function query(): Builder
    {
        return SupportRequest::query()->where('account_id', $this->user->account_id);
    }
}
