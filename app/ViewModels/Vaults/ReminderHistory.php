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

class ReminderHistory
{
    private const int LIMIT = 50;

    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
    ) {}

    /** @return list<array{date: string, time: string, title: string, note: string, tone: string}> */
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

    /** @return Collection<int, array{at: CarbonImmutable, title: string, note: string, tone: string}> */
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
        return match ($channel) {
            NotificationChannelEnum::InApp => __('It appeared in the application.'),
            NotificationChannelEnum::Email => __('It went out by email.'),
            NotificationChannelEnum::Push => __('It was pushed to a device.'),
        };
    }

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
