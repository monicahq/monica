<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * What this vault has already told somebody about.
 *
 * It answers a different question from the list beside it, and it is the first
 * place to look when a reminder seems not to have arrived: the Active tab says
 * what is coming, this says what went out.
 *
 * A row is a deadline that was notified, not a notification, because a deadline
 * that reached somebody in the app and by email is one thing that happened and
 * would otherwise be listed twice. The channels it went out on are read off the
 * notifications underneath it and drawn as marks on the one row.
 *
 * It keys off `notified_at` rather than off status, and that has two
 * consequences worth stating. A row survives the deletion of the rule that made
 * it, which is what Principle VIII asks of history. And a test send, which
 * deliberately leaves `notified_at` alone, never appears here, so pressing that
 * button cannot pollute the record of what actually reached somebody.
 *
 * The history is unbounded, unlike the ninety day window next door, so it
 * paginates. It is drawn the way every other long list in this application is,
 * as a page with a way to ask for the next one.
 */
class VaultRemindersSentViewModel
{
    public const int PER_PAGE = 25;

    /** @var Paginator<int, ReminderOccurrence>|null */
    private ?Paginator $occurrences = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        return __('Reminders');
    }

    public function intro(): string
    {
        return __('Every reminder this vault has actually sent, newest first, and what became of it afterwards.');
    }

    /**
     * The rows of this page, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        return array_map(
            fn (ReminderOccurrence $occurrence): array => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            )->sent(),
            $this->occurrences()->items(),
        );
    }

    public function hasMorePages(): bool
    {
        return $this->occurrences()->hasMorePages();
    }

    public function nextPageUrl(): ?string
    {
        return $this->occurrences()->nextPageUrl();
    }

    public function emptyTitle(): string
    {
        return __('Nothing has been sent yet');
    }

    public function emptyLine(): string
    {
        return __('As soon as a reminder in this vault comes due and reaches you, it is written down here, with the way it arrived and what you did with it.');
    }

    public function activeUrl(): string
    {
        return route('reminders.index', $this->vault);
    }

    public function sentUrl(): string
    {
        return route('reminders.sent', $this->vault);
    }

    /**
     * How many deadlines are still coming, for the tab beside this one. It is
     * counted rather than listed, since this screen never draws them.
     */
    public function activeCount(): int
    {
        return ReminderOccurrence::query()
            ->upcomingInVault(
                vault: $this->vault,
                until: CarbonImmutable::now()->addDays((int) config('monica.reminder_generation_window_in_days')),
            )
            ->count();
    }

    public function sentCount(): int
    {
        return ReminderOccurrence::query()->sentInVault($this->vault)->count();
    }

    /**
     * @return Paginator<int, ReminderOccurrence>
     */
    private function occurrences(): Paginator
    {
        return $this->occurrences ??= ReminderOccurrence::query()
            ->sentInVault($this->vault)
            ->with(['vault', 'reminder.remindable.avatar', 'reminder.field', 'notifications'])
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();
    }
}
