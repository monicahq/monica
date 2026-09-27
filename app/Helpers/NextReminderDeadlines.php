<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The next date of each active reminder after today, one per reminder.
 *
 * Deadlines are generated a few weeks ahead only, so a yearly birthday has no row for most of the
 * year. A reminder without one is given an unsaved deadline built from its schedule: it can be
 * shown, and it cannot be answered, which nothing upcoming can be anyway.
 */
class NextReminderDeadlines
{
    /**
     * @param  EloquentCollection<int, Reminder>  $rules
     * @return Collection<int, ReminderOccurrence>
     */
    public static function for(EloquentCollection $rules, CarbonImmutable $today): Collection
    {
        $generated = ReminderOccurrence::query()
            ->whereIn('reminder_id', $rules->modelKeys())
            ->active()
            ->whereDate('local_date', '>', $today->toDateString())
            ->orderBy('local_date')
            ->get()
            ->unique('reminder_id')
            ->keyBy('reminder_id');

        return $rules
            ->map(function (Reminder $rule) use ($generated, $today): ?ReminderOccurrence {
                $occurrence = $generated->get($rule->id) ?? self::computed($rule, $today);

                return $occurrence?->setRelation('reminder', $rule)->setRelation('vault', $rule->vault);
            })
            ->filter()
            ->sortBy(fn (ReminderOccurrence $occurrence): string => $occurrence->local_date->toDateString())
            ->values();
    }

    private static function computed(Reminder $rule, CarbonImmutable $today): ?ReminderOccurrence
    {
        $tomorrow = max(
            $today->addDay()->toDateString(),
            CarbonImmutable::now($rule->timezone)->addDay()->toDateString(),
        );

        $date = ReminderSchedule::nextDate($rule, CarbonImmutable::parse($tomorrow, $rule->timezone)->startOfDay());

        if ($date === null) {
            return null;
        }

        return new ReminderOccurrence([
            'account_id' => $rule->account_id,
            'vault_id' => $rule->vault_id,
            'reminder_id' => $rule->id,
            'local_date' => $date->toDateString(),
            'scheduled_for' => ReminderSchedule::instantOf($rule, $date),
        ]);
    }
}
