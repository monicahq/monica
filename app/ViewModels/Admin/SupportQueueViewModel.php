<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\SupportCategoryEnum;
use App\Enums\SupportMessageKindEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Helpers\PageSize;
use App\Helpers\StorageSize;
use App\Helpers\SupportMarkdown;
use App\Helpers\SupportQueue;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class SupportQueueViewModel
{
    public const int PER_PAGE = 50;

    public function __construct(
        private readonly User $administrator,
        private readonly SupportQueue $queue,
        private readonly int|string $size = self::PER_PAGE,
        private readonly ?SupportRequest $selected = null,
    ) {}

    public function queue(): SupportQueue
    {
        return $this->queue;
    }

    public function clearFiltersUrl(): string
    {
        return route('admin.support.index', ['view' => $this->queue->view]);
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /** @return list<array{key: string, label: string, count: int, url: string, selected: bool}> */
    public function views(): array
    {
        $counts = $this->queue->counts();
        $labels = [];

        foreach (SupportRequestStatusEnum::cases() as $status) {
            $labels[$status->value] = __($status->administratorLabel());
        }

        $labels[SupportQueue::ALL] = __('All');

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $labels[$key],
            'count' => $counts[$key],
            'url' => route('admin.support.index', [...$this->queue->parameters(), 'view' => $key]),
            'selected' => $key === $this->queue->view,
        ], array_keys($labels));
    }

    /** @return list<array{value: string, label: string}> */
    public function categoryOptions(): array
    {
        return [
            ['value' => '', 'label' => __('All categories')],
            ...array_map(fn (SupportCategoryEnum $category): array => ['value' => $category->value, 'label' => __($category->shortLabel())], SupportCategoryEnum::cases()),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function userOptions(): array
    {
        $users = User::query()
            ->whereIn('id', SupportRequest::query()->whereNotNull('user_id')->select('user_id'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
            ->all();

        return [['value' => '', 'label' => __('All users')], ...$users];
    }

    /** @return list<array{value: string, label: string}> */
    public function ageOptions(): array
    {
        return [
            ['value' => '', 'label' => __('Any age')],
            ['value' => 'day', 'label' => __('Under 24 hours')],
            ['value' => 'week', 'label' => __('1 to 7 days')],
            ['value' => 'old', 'label' => __('Older than 7 days')],
        ];
    }

    /** @return LengthAwarePaginator<int, SupportRequest> */
    public function requests(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            $this->queue->query()->with(['user', 'messages']),
            $this->size,
        )->withQueryString());
    }

    /** @return list<array{uuid: string, url: string, current: bool, subject: string, name: string, categoryLabel: string, age: string, lastLine: string}> */
    public function rows(): array
    {
        $current = $this->current();

        return collect($this->requests()->items())
            ->map(function (SupportRequest $supportRequest) use ($current): array {
                $last = $supportRequest->messages->last(fn (SupportMessage $message): bool => $message->kind !== SupportMessageKindEnum::Note);
                $lastLine = match (true) {
                    $last === null => '',
                    $last->kind === SupportMessageKindEnum::Reply => __('Support: :body', ['body' => SupportMarkdown::plainText($last->body)]),
                    default => SupportMarkdown::plainText($last->body),
                };

                return [
                    'uuid' => $supportRequest->uuid,
                    'url' => route('admin.support.show', [...$this->queue->parameters(), 'supportRequest' => $supportRequest->uuid]),
                    'current' => $current?->id === $supportRequest->id,
                    'subject' => $supportRequest->subject,
                    'name' => $supportRequest->user->name ?? __('Deleted user'),
                    'categoryLabel' => __($supportRequest->category->shortLabel()),
                    'age' => $supportRequest->created_at->diffForHumans(),
                    'lastLine' => $lastLine,
                ];
            })
            ->values()
            ->all();
    }

    public function countLine(): string
    {
        return trans_choice(':count request|:count requests', $this->requests()->total());
    }

    public function emptyTitle(): string
    {
        if ($this->queue->hasFilters()) {
            return __('Nothing matches these filters');
        }

        return $this->queue->view === SupportRequestStatusEnum::ToHandle->value ? __('Nothing to handle') : __('Nobody here yet');
    }

    public function emptyBody(): string
    {
        if ($this->queue->hasFilters()) {
            return __('Clear the filters or try another view.');
        }

        return $this->queue->view === SupportRequestStatusEnum::ToHandle->value
            ? __('New requests, and replies from people you were waiting on, land here.')
            : __('Requests move here when their status changes.');
    }

    public function current(): ?SupportRequest
    {
        return once(fn (): ?SupportRequest => $this->selected ?? $this->requests()->items()[0] ?? null);
    }

    public function context(): ?SupportRequestContext
    {
        $current = $this->current();

        return $current === null ? null : new SupportRequestContext($current);
    }

    /** @return array{uuid: string, reference: string, subject: string, status: string, statusLabel: string, statusTone: string, statusIcon: string, categoryLabel: string, opened: string, firstName: string, email: string}|null */
    public function currentSummary(): ?array
    {
        $current = $this->current();

        if ($current === null) {
            return null;
        }

        return [
            'uuid' => $current->uuid,
            'reference' => $current->reference,
            'subject' => $current->subject,
            'status' => $current->status->value,
            'statusLabel' => __($current->status->administratorLabel()),
            'statusTone' => $current->status->administratorTone(),
            'statusIcon' => $current->status->icon(),
            'categoryLabel' => __($current->category->shortLabel()),
            'opened' => $current->created_at->diffForHumans(),
            'firstName' => $current->user->first_name ?? __('the user'),
            'email' => $current->user->email ?? '',
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function statusOptions(): array
    {
        return array_map(fn (SupportRequestStatusEnum $status): array => [
            'value' => $status->value,
            'label' => __($status->administratorLabel()),
        ], SupportRequestStatusEnum::cases());
    }

    /** @return list<array{id: string, author: string, kind: string, when: string, body: string, files: list<array{name: string, size: string, url: string}>}>
     */
    public function thread(): array
    {
        $current = $this->current();

        if ($current === null) {
            return [];
        }

        return $current->messages()
            ->with('attachments')
            ->get()
            ->map(fn (SupportMessage $message): array => [
                'id' => $message->uuid,
                'author' => $message->author_name,
                'kind' => $message->kind->value,
                'when' => $message->created_at->setTimezone($this->administrator->timezone)->isoFormat('LLL'),
                'body' => $message->body,
                'files' => $message->attachments->map(fn (SupportAttachment $attachment): array => [
                    'name' => $attachment->original_name,
                    'size' => StorageSize::readable($attachment->size_in_bytes),
                    'url' => route('admin.support.attachments.show', ['supportAttachment' => $attachment->uuid]),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{line: string, by: string, when: string, tone: string}>
     */
    public function history(): array
    {
        $current = $this->current();

        if ($current === null) {
            return [];
        }

        return $current->statusChanges()
            ->get()
            ->reverse()
            ->map(fn (SupportStatusChange $change): array => [
                'line' => $change->from_status === null ? __('Opened') : __('Moved to :status', ['status' => __($change->to_status->administratorLabel())]),
                'by' => $change->user_name,
                'when' => $change->created_at->setTimezone($this->administrator->timezone)->isoFormat('LLL'),
                'tone' => $change->to_status->administratorTone(),
            ])
            ->values()
            ->all();
    }
}
