<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\NextReminderDeadlines;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * The dates of a vault still waiting for somebody: every deadline due by today that nobody has
 * answered, then the next deadline of each active reminder. The reminders screen and the dashboard
 * both list these, so they read them from one place. The dashboard stops at a day, and asks the
 * database for that span rather than building every row.
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
            ->with(['reminder.remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'reminder.field'])
            ->get()
            ->each(fn (ReminderOccurrence $occurrence): ReminderOccurrence => $occurrence->setRelation('vault', $this->vault));

        $rules = Reminder::query()
            ->where('vault_id', $this->vault->id)
            ->where('account_id', $this->vault->account_id)
            ->active()
            ->with(['remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'field', 'fieldValue'])
            ->get()
            ->each(fn (Reminder $rule): Reminder => $rule->setRelation('vault', $this->vault));

        return $waiting
            ->concat(NextReminderDeadlines::for($rules, $today))
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ))
            ->values();
    }

    /** @return Collection<int, ReminderRow> */
    public function rowsUntil(CarbonImmutable $lastDay): Collection
    {
        $today = CarbonImmutable::now($this->user->timezone)->startOfDay();
        $window = (int) config('monica.reminder_generation_window_in_days');

        $waiting = ReminderOccurrence::query()
            ->awaitingAnswerInVault(vault: $this->vault, untilDay: $today)
            ->with(['vault', 'reminder.remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'reminder.field'])
            ->get();

        $generated = ReminderOccurrence::query()
            ->where('vault_id', $this->vault->id)
            ->where('account_id', $this->vault->account_id)
            ->active()
            ->where('local_date', '>=', $today->addDay()->toDateString())
            ->where('local_date', '<', $lastDay->addDay()->toDateString())
            ->whereHas('reminder', function (Builder $rule): void {
                $rule->where('is_active', true);
            })
            ->with(['vault', 'reminder.vault', 'reminder.remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'reminder.field', 'reminder.fieldValue'])
            ->orderBy('local_date')
            ->orderBy('scheduled_for')
            ->get()
            ->unique('reminder_id');

        // A rule has every deadline written until `generate_from` plus the window. Only a rule whose
        // written deadlines stop before the last day, two days of margin covering the time zones, is
        // worked out from its schedule.
        $behind = Reminder::query()
            ->where('vault_id', $this->vault->id)
            ->where('account_id', $this->vault->account_id)
            ->active()
            ->where('generate_from', '<=', $lastDay->addDays(2)->subDays($window))
            ->whereNotIn('id', $generated->pluck('reminder_id')->all())
            ->with(['vault', 'remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'field', 'fieldValue'])
            ->get();

        $computed = NextReminderDeadlines::for($behind, $today)
            ->filter(fn (ReminderOccurrence $occurrence): bool => $occurrence->local_date->toDateString() <= $lastDay->toDateString());

        return $waiting
            ->concat($generated->concat($computed)->sortBy(fn (ReminderOccurrence $occurrence): string => $occurrence->local_date->toDateString()))
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ))
            ->values();
    }
}
