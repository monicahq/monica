<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Mark one notification read, or every unread one the person has.
 *
 * Reading is a fact about the person rather than about the account, so the
 * query is scoped to the user and to the account behind them. A notification
 * already read keeps the instant it was first read, because that is when it was
 * read.
 */
class MarkNotificationsAsRead
{
    public function __construct(
        private readonly User $user,
        private readonly ?Notification $notification = null,
    ) {}

    public function execute(): int
    {
        if ($this->notification === null) {
            return $this->all();
        }

        return $this->one($this->notification);
    }

    private function one(Notification $notification): int
    {
        if ($notification->account_id !== $this->user->account_id || $notification->user_id !== $this->user->id) {
            throw new ModelNotFoundException('This notification belongs to somebody else.');
        }

        if ($notification->read_at !== null) {
            return 0;
        }

        $notification->read_at = now();
        $notification->save();

        return 1;
    }

    private function all(): int
    {
        return Notification::query()
            ->where('account_id', $this->user->account_id)
            ->where('user_id', $this->user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }
}
