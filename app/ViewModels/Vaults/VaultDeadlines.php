<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\NextReminderDeadlines;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The dates of a vault still waiting for somebody: every deadline due by today that nobody has
 * answered, then the next deadline of each active reminder. The reminders screen and the dashboard
 * both list these, so they read them from one place.
 */
class VaultDeadlines
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    /** @return Collection<int, ReminderRow> */
    public function rows(): Collection
    {
        $today = CarbonImmutable::now($this->user->timezone)->startOfDay();

        $waiting = ReminderOccurrence::query()
            ->awaitingAnswerInVault(vault: $this->vault, untilDay: $today)
            ->with(['vault', 'reminder.remindable.avatar', 'reminder.field'])
            ->get();

        $rules = Reminder::query()
            ->where('vault_id', $this->vault->id)
            ->where('account_id', $this->vault->account_id)
            ->active()
            ->with(['vault', 'remindable.avatar', 'field', 'fieldValue'])
            ->get();

        return $waiting
            ->concat(NextReminderDeadlines::for($rules, $today))
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ))
            ->values();
    }
}
