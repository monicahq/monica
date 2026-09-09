<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\Paginator;

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
        return __('Every reminder this vault has sent, newest first, and what became of it afterwards.');
    }

    /** @return list<ReminderRow> */
    public function rows(): array
    {
        return array_map(
            fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ),
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
        return __('A reminder that comes due and reaches you is written down here, with the way it arrived and what you did with it.');
    }

    public function activeUrl(): string
    {
        return route('reminders.index', $this->vault);
    }

    public function sentUrl(): string
    {
        return route('reminders.sent', $this->vault);
    }

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

    /** @return Paginator<int, ReminderOccurrence> */
    private function occurrences(): Paginator
    {
        return $this->occurrences ??= ReminderOccurrence::query()
            ->sentInVault($this->vault)
            ->with(['vault', 'reminder.remindable.avatar', 'reminder.field', 'notifications'])
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();
    }
}
