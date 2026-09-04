<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailType;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendEmail;
use App\Mail\NotificationDigest;
use App\Models\Account;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Fold everything an account has waiting into one message.
 *
 * Nothing is dropped by folding. Each notification keeps its own row, its own
 * words and its own record of when it was due, and gains a pointer to the
 * summary that carried it, so the history of what somebody was told stays
 * complete and a screen can still show the deadlines one by one.
 *
 * A summary with nothing in it is not sent. There is no news in "nothing
 * happened today", and an empty message every evening is how somebody learns to
 * ignore the ones that matter.
 *
 * The summary is about several things at once, so it is about nothing in
 * particular: it carries no notifiable, which is also what keeps it out of the
 * index that stops anybody being told twice.
 */
class SendNotificationDigest
{
    public function __construct(
        private readonly Account $account,
    ) {}

    /**
     * How many notifications were folded into the summary, and zero when there
     * was nothing to say.
     */
    public function execute(): int
    {
        $user = $this->account->user;

        if ($user === null) {
            return 0;
        }

        $waiting = $this->waitingFor($user);

        if ($waiting->isEmpty()) {
            return 0;
        }

        $digest = $this->write($user, $waiting);

        Notification::query()
            ->whereIn('id', $waiting->pluck('id'))
            ->update(['digest_id' => $digest->id, 'sent_at' => now(), 'updated_at' => now()]);

        $this->hand($user, $digest);

        return $waiting->count();
    }

    /**
     * @return Collection<int, Notification>
     */
    private function waitingFor(User $user): Collection
    {
        return Notification::query()
            ->deliverable()
            ->where('user_id', $user->id)
            ->where('type', '!=', NotificationTypeEnum::Digest->value)
            ->orderBy('scheduled_for')
            ->get();
    }

    /**
     * @param  Collection<int, Notification>  $waiting
     */
    private function write(User $user, Collection $waiting): Notification
    {
        $locale = $user->locale ?? (string) config('app.locale');

        return Notification::query()->create([
            'account_id' => $this->account->id,
            'user_id' => $user->id,
            'type' => NotificationTypeEnum::Digest,
            'channel' => NotificationChannelEnum::Email,
            'notifiable_type' => null,
            'notifiable_id' => null,
            'title' => trans_choice('You have :count reminder today|You have :count reminders today', $waiting->count(), [], $locale),
            'body' => $waiting->map(fn (Notification $one): string => $one->title)->implode("\n"),
            'payload' => [
                'lines' => $waiting->map(fn (Notification $one): array => [
                    'title' => $one->title,
                    'body' => $one->body,
                ])->values()->all(),
            ],
            'locale' => $locale,
            'scheduled_for' => now(),
            'held_until' => now(),
            'sent_at' => now(),
            'attempts' => 1,
        ]);
    }

    private function hand(User $user, Notification $digest): void
    {
        /** @var list<array{title: string, body: string}> $lines */
        $lines = $digest->payload['lines'] ?? [];

        SendEmail::dispatch(
            mailable: new NotificationDigest(
                heading: $digest->title,
                lines: $lines,
            )->locale($digest->locale),
            account: $this->account,
            emailType: EmailType::NotificationDigest,
            user: $user,
        )->onQueue('high');
    }
}
