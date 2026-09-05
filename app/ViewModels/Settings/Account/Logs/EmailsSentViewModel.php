<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Logs;

use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;

class EmailsSentViewModel
{
    private const int PER_PAGE = 10;

    /** @var CursorPaginator<int, EmailSent>|null */
    private ?CursorPaginator $emailsSent = null;

    public function __construct(
        private readonly User $user,
    ) {}

    /** @return CursorPaginator<int, EmailSent> */
    public function emailsSent(): CursorPaginator
    {
        return $this->emailsSent ??= $this->user->emailsSent()
            ->latest('sent_at')
            ->cursorPaginate(self::PER_PAGE);
    }

    public function user(): User
    {
        return $this->user;
    }
}
