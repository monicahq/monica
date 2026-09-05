<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Logs;

use App\Models\EmailSent;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;

class LogsViewModel
{
    private const int PER_PAGE = 5;

    private const int EMAILS_SHOWN = 5;

    /** @var CursorPaginator<int, Log>|null */
    private ?CursorPaginator $logs = null;

    public function __construct(
        private readonly User $user,
    ) {}

    /** @return CursorPaginator<int, Log> */
    public function logs(): CursorPaginator
    {
        return $this->logs ??= $this->user->account->logs()
            ->with('user')
            ->latest()
            ->cursorPaginate(self::PER_PAGE);
    }

    /** @return Collection<int, EmailSent> */
    public function emailsSent(): Collection
    {
        return $this->user->emailsSent()
            ->latest('sent_at')
            ->take(self::EMAILS_SHOWN)
            ->get();
    }

    public function hasMoreEmailsSent(): bool
    {
        return $this->user->emailsSent()->count() > self::EMAILS_SHOWN;
    }

    public function user(): User
    {
        return $this->user;
    }
}
