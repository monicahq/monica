<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\NotificationChannelEnum;
use App\Helpers\AvailableLocales;
use App\Models\Log;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReminderHistory
{
    public const int LIMIT = 50;

    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
        private readonly int $page = 1,
    ) {}

    /** @return list<array{date: string, time: string, title: string, note: string, tone: string}> */
    public function get(): array
    {
        return $this->merged()
            ->slice(($this->page - 1) * self::LIMIT, self::LIMIT)
            ->values()
            ->map(fn (array $event): array => [
                'date' => DateFormatEnum::write($event['at'], $this->dateFormat()),
                'time' => $this->user->time_format->format($event['at']),
                'title' => $event['title'],
                'note' => $event['note'],
                'tone' => $event['tone'],
            ])
            ->all();
    }

    public function hasMore(): bool
    {
        return $this->merged()->count() > $this->page * self::LIMIT;
    }

    /** @return Collection<int, array{at: CarbonImmutable, title: string, note: string, tone: string}> */
    private function merged(): Collection
    {
        return once(fn (): Collection => $this->fromTheJournal()
            ->merge($this->fromWhatWentOut())
            ->merge($this->fromWhatIsNoLongerKept())
            ->sortByDesc(fn (array $event): CarbonImmutable => $event['at'])
            ->values());
    }

    /**
     * Each source reads one row past the page's end, so a source that alone fills
     * every page so far still reveals whether it holds a next one.
     */
    private function window(): int
    {
        return $this->page * self::LIMIT + 1;
    }

    /** @return Collection<int, array{at: CarbonImmutable, title: string, note: string, tone: string}> */
    private function fromTheJournal(): Collection
    {
        $deadlines = $this->reminder->occurrences()->pluck('id')->all();

        return Log::query()
            ->with('user')
            ->where('account_id', $this->reminder->account_id)
            ->where(function ($query) use ($deadlines): void {
                $query
                    ->where(function ($rule): void {
                        $rule
                            ->where('loggable_type', Reminder::class)
                            ->where('loggable_id', $this->reminder->id);
                    })
                    ->orWhere(function ($deadline) use ($deadlines): void {
                        $deadline
                            ->where('loggable_type', ReminderOccurrence::class)
                            ->whereIn('loggable_id', $deadlines);
                    });
            })
            ->latest()
            ->take($this->window())
            ->get()
            ->toBase()
            ->map(fn (Log $log): array => [
                'at' => $this->readable($log->created_at),
                'title' => $log->description,
                'note' => $this->doneBy($log),
                'tone' => 'neutral',
            ]);
    }

    /** @return Collection<int, array{at: CarbonImmutable, title: string, note: string, tone: string}> */
    private function fromWhatWentOut(): Collection
    {
        return Notification::query()
            ->where('account_id', $this->reminder->account_id)
            ->where('notifiable_type', ReminderOccurrence::class)
            ->whereIn('notifiable_id', $this->reminder->occurrences()->select('id'))
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->take($this->window())
            ->get()
            ->toBase()
            ->map(fn (Notification $notification): array => [
                'at' => $this->readable($notification->sent_at ?? $notification->scheduled_for),
                'title' => $this->delivered(),
                'note' => $this->wentOutOn($notification->channel),
                'tone' => 'success',
            ]);
    }

    /**
     * The deadlines that were notified but whose per channel records have been
     * pruned.
     *
     * `notified_at` on the deadline outlives the notifications, so the history
     * can still say somebody was told. What it cannot say any more is which
     * channel carried it, and saying so plainly is better than a gap the reader
     * has to explain to themselves.
     *
     * @return Collection<int, array{at: CarbonImmutable, title: string, note: string, tone: string}>
     */
    private function fromWhatIsNoLongerKept(): Collection
    {
        $stillRecorded = Notification::query()
            ->where('account_id', $this->reminder->account_id)
            ->where('notifiable_type', ReminderOccurrence::class)
            ->whereIn('notifiable_id', $this->reminder->occurrences()->select('id'))
            ->whereNotNull('sent_at')
            ->pluck('notifiable_id')
            ->all();

        return $this->reminder->occurrences()
            ->whereNotNull('notified_at')
            ->whereNotIn('id', $stillRecorded)
            ->latest('notified_at')
            ->take($this->window())
            ->get()
            ->toBase()
            ->map(fn (ReminderOccurrence $occurrence): array => [
                'at' => $this->readable($occurrence->notified_at),
                'title' => $this->delivered(),
                'note' => (string) __('Which channel carried it is no longer kept.'),
                'tone' => 'neutral',
            ]);
    }

    private function doneBy(Log $log): string
    {
        return __('By :author.', ['author' => $log->author]);
    }

    private function delivered(): string
    {
        return __('Notification sent');
    }

    private function wentOutOn(NotificationChannelEnum $channel): string
    {
        return __($channel->deliveredSentence());
    }

    private function readable(CarbonImmutable $at): CarbonImmutable
    {
        return $at->setTimezone($this->reminder->timezone)->locale(app()->getLocale());
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
