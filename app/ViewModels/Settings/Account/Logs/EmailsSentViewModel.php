<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Logs;

use App\Models\EmailSent;
use App\Models\Employee;
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
        private readonly ?Employee $employee,
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
     * The name to show and to draw initials from. Somebody whose account is not
     * attached to an employee record has only an email address to go by.
     */
    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    /**
     * The record the avatar draws from, so the sidebar can show the photo when
     * there is one. An account that belongs to nobody who works here has none.
     */
    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function companyName(): string
    {
        return $this->user->company->name;
    }
}
