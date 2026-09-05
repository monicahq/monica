<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\NotificationChannelEnum;
use App\Models\Log;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Everything that has happened to one reminder, newest first.
 *
 * It is assembled rather than stored. Every action somebody performs on a
 * reminder already writes a line in the account's journal, with the rule or the
 * deadline it was about hung off it, so a second record of the same events would
 * be a second thing to keep in step and one that would start empty for every
 * reminder that already exists.
 *
 * Two sources, because they answer different questions. The journal says what
 * somebody did; it has nothing to say about delivery, since a notification going
 * out is not somebody's action. What actually reached a person comes from the
 * notifications written for the deadlines instead, and only those that really
 * went out: one still waiting is not history.
 *
 * The journal is read across the rule and its deadlines at once. Putting a
 * deadline off is recorded against the deadline, and somebody reading the rule's
 * history wants it there, so the two are one list rather than two panels that
 * would have to be read alternately.
 *
 * What this cannot show is anything from before the feature existed, and each
 * step of a deadline moved more than once: the row keeps where it started and
 * where it is now, and the journal keeps one line per move, so the count is
 * right and the intermediate instants are only in those lines.
 */
class ReminderHistory
{
    /**
     * How many events one panel draws. A reminder that has come round every year
     * for a decade has a few hundred, and a page that drew all of them would be
     * a page about its own scrollbar.
     */
    private const int LIMIT = 50;

    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
    ) {}

    /**
     * @return list<array{date: string, time: string, title: string, note: string, tone: string}>
     */
    public function get(): array
    {
        $events = $this->fromTheJournal()
            ->merge($this->fromWhatWentOut())
            ->sortByDesc(fn (array $event): CarbonImmutable => $event['at'])
            ->take(self::LIMIT)
            ->values();

        return $events
            ->map(fn (array $event): array => [
                'date' => DateFormatEnum::write($event['at'], $this->dateFormat()),
                'time' => $this->user->time_format->format($event['at']),
                'title' => $event['title'],
                'note' => $event['note'],
                'tone' => $event['tone'],
            ])
            ->all();
    }

    /**
     * What somebody did, taken from the account's journal.
     *
     * The account is asked for as well as the record, because `loggable_id` is a
     * plain column rather than a foreign key: an id of another account's rule
     * would otherwise match on the number alone.
     *
     * @return Collection<int, array{at: CarbonImmutable, title: string, note: string, tone: string}>
     */
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
            ->take(self::LIMIT)
            ->get()
            ->toBase()
            ->map(fn (Log $log): array => [
                'at' => $this->readable($log->created_at),
                'title' => $log->description,
                'note' => $this->doneBy($log),
                'tone' => 'neutral',
            ]);
    }

    /**
     * What actually reached somebody, one entry per notification that went out.
     *
     * They are not folded into one entry per deadline. A reminder that arrives in
     * the application and again by email arrived twice, and a reader checking
     * whether the email really went is asking about the email.
     *
     * @return Collection<int, array{at: CarbonImmutable, title: string, note: string, tone: string}>
     */
    private function fromWhatWentOut(): Collection
    {
        return Notification::query()
            ->where('account_id', $this->reminder->account_id)
            ->where('notifiable_type', ReminderOccurrence::class)
            ->whereIn('notifiable_id', $this->reminder->occurrences()->select('id'))
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->take(self::LIMIT)
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
     * Who performed the action, as a sentence. It is its own method for the
     * reason {@see wentOutOn()} is: a closure has no declared shape, so the
     * sentence has to be typed where it is written.
     */
    private function doneBy(Log $log): string
    {
        return __('By :author.', ['author' => $log->author]);
    }

    /**
     * What a delivery is called. The journal records what somebody did and a
     * notification going out is not that, so this is the one line in the panel
     * with no action behind it.
     */
    private function delivered(): string
    {
        return __('Notification sent');
    }

    /**
     * One channel in words. It is said here rather than on the enum because the
     * sentence is about one notification that already went, which is not what
     * the list of channels on the delivery panel is saying.
     */
    private function wentOutOn(NotificationChannelEnum $channel): string
    {
        return match ($channel) {
            NotificationChannelEnum::InApp => __('It appeared in the application.'),
            NotificationChannelEnum::Email => __('It went out by email.'),
            NotificationChannelEnum::Push => __('It was pushed to a device.'),
        };
    }

    /**
     * An instant in the zone frozen on the rule, like every other instant on this
     * screen. What happened, happened at the reminder's own hour, and a history
     * written in the reader's zone would disagree with the dates above it.
     */
    private function readable(CarbonImmutable $at): CarbonImmutable
    {
        return $at->setTimezone($this->reminder->timezone)->locale(app()->getLocale());
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) config('monica.locales.'.app()->getLocale().'.date_format', DateFormatEnum::MonthSpelled->value);
    }
}
