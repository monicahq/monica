<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Logs;

use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * What the screen listing every email we sent shows: the emails themselves, a
 * page at a time, and the shell around them.
 */
class EmailsSentViewModel
{
    /**
     * How many emails a page holds.
     */
    private const int PER_PAGE = 10;

    /**
     * The page is asked for more than once while the screen renders, and each
     * ask would be another query, so the first one is kept.
     *
     * @var CursorPaginator<int, EmailSent>|null
     */
    private ?CursorPaginator $emailsSent = null;

    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * The emails sent to the person signed in, newest first.
     *
     * @return CursorPaginator<int, EmailSent>
     */
    public function emailsSent(): CursorPaginator
    {
        return $this->emailsSent ??= $this->user->emailsSent()
            ->latest('sent_at')
            ->cursorPaginate(self::PER_PAGE);
    }

    /**
     * Who is signed in, so the sidebar can show their name and their photo.
     */
    public function user(): User
    {
        return $this->user;
    }
}
