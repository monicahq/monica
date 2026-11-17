<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationTypeEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Fold everything an account has waiting into one digest per channel, so a channel that refuses its
 * digest takes no other channel down with it. A channel that already had its digest since the chosen
 * time gets none, which is what lets a channel that refused it be tried again on the next run.
 */
class SendNotificationDigest
{
    public function __construct(
        private readonly Account $account,
        private readonly CarbonInterface $since,
    ) {}

    public function execute(): int
    {
        $user = $this->account->user;

        if ($user === null) {
            return 0;
        }

        $folded = 0;

        foreach ($this->channels() as $channel) {
            if ($this->alreadySent($channel)) {
                continue;
            }

            $folded += new DeliverNotifications(notifications: $this->waitingFor($user, $channel), asDigest: true)->execute();
        }

        return $folded;
    }

    private function alreadySent(NotificationChannel $channel): bool
    {
        return Notification::query()
            ->where('account_id', $this->account->id)
            ->where('type', NotificationTypeEnum::Digest->value)
            ->where('channel', $channel->channel->value)
            ->where('created_at', '>=', $this->since)
            ->exists();
    }

    /** @return Collection<int, NotificationChannel> */
    private function channels(): Collection
    {
        return NotificationChannel::query()
            ->where('account_id', $this->account->id)
            ->where('is_enabled', true)
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, Notification> */
    private function waitingFor(User $user, NotificationChannel $channel): Collection
    {
        return Notification::query()
            ->deliverable()
            ->where('user_id', $user->id)
            ->where('channel', $channel->channel->value)
            ->where('type', '!=', NotificationTypeEnum::Digest->value)
            ->orderBy('scheduled_for')
            ->with('user', 'notifiable')
            ->get();
    }
}
