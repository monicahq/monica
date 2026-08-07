<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Logs;

use App\Models\EmailSent;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;

/**
 * What the logs screen shows: every action the signed in person performed, a
 * page at a time, the last few emails we sent them, and the shell around it.
 */
class LogsViewModel
{
    /**
     * How many entries a page holds. The screen loads the next page in place
     * rather than sending the reader to a second one, so the page is small
     * enough that asking for more is cheap.
     */
    private const int PER_PAGE = 5;

    /**
     * How many emails the box shows before it sends somebody to the screen that
     * lists them all.
     */
    private const int EMAILS_SHOWN = 5;

    /** @var CursorPaginator<int, Log>|null */
    private ?CursorPaginator $logs = null;

    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * The actions of the signed in person, newest first. The author of each
     * entry is read off the user, so both are loaded up front. The screen asks
     * for the page more than once, and one page is one query.
     *
     * @return CursorPaginator<int, Log>
     */
    public function logs(): CursorPaginator
    {
        return $this->logs ??= $this->user->logs()
            ->with('user')
            ->latest()
            ->cursorPaginate(self::PER_PAGE);
    }

    /**
     * The last few emails the application sent to the person signed in, newest
     * first.
     *
     * @return Collection<int, EmailSent>
     */
    public function emailsSent(): Collection
    {
        return $this->user->emailsSent()
            ->latest('sent_at')
            ->take(self::EMAILS_SHOWN)
            ->get();
    }

    /**
     * Whether there are more emails than the box shows, so the screen knows
     * whether the link to the whole list is worth offering.
     */
    public function hasMoreEmailsSent(): bool
    {
        return $this->user->emailsSent()->count() > self::EMAILS_SHOWN;
    }

    /**
     * Who is signed in, so the sidebar can show their name and their photo.
     */
    public function user(): User
    {
        return $this->user;
    }
}
