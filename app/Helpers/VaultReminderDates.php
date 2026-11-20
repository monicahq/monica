<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\DefaultFieldEnum;
use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The dates of a whole vault. The dashboard, the API and the MCP server all read them from here, so
 * the three give the same answer for the same day.
 */
class VaultReminderDates
{
    public const string All = 'all';

    public const string Birthdays = 'birthdays';

    public const int DefaultDays = ReminderOccurrenceCategoryEnum::UpcomingDays;

    public const int MaximumDays = 365;

    /** @return list<string> */
    public static function kinds(): array
    {
        return [self::All, self::Birthdays];
    }

    /**
     * Every deadline due by today that nobody has answered, then the next deadline of each active
     * reminder up to the last day.
     *
     * @return Collection<int, ReminderOccurrence>
     */
    public static function until(Vault $vault, CarbonImmutable $today, CarbonImmutable $lastDay): Collection
    {
        $window = (int) config('monica.reminder_generation_window_in_days');

        $waiting = ReminderOccurrence::query()
            ->awaitingAnswerInVault(vault: $vault, untilDay: $today)
            ->with(['vault', 'reminder.remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'reminder.field', 'reminder.fieldValue'])
            ->get();

        $generated = ReminderOccurrence::query()
            ->where('vault_id', $vault->id)
            ->where('account_id', $vault->account_id)
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
            ->where('vault_id', $vault->id)
            ->where('account_id', $vault->account_id)
            ->active()
            ->where('generate_from', '<=', $lastDay->addDays(2)->subDays($window))
            ->whereNotIn('id', $generated->pluck('reminder_id')->all())
            ->with(['vault', 'remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'field', 'fieldValue'])
            ->get();

        $computed = NextReminderDeadlines::for($behind, $today)
            ->filter(fn (ReminderOccurrence $occurrence): bool => $occurrence->local_date->toDateString() <= $lastDay->toDateString());

        return $waiting
            ->concat($generated->concat($computed)->sortBy(fn (ReminderOccurrence $occurrence): string => $occurrence->local_date->toDateString()))
            ->values();
    }

    /** @return LengthAwarePaginator<int, VaultReminderDate> */
    public static function page(User $user, Vault $vault, ?string $category, string $kind, int $days, bool $reachesSensitiveFields, int $perPage, int $page): LengthAwarePaginator
    {
        $today = CarbonImmutable::now($user->timezone)->startOfDay();

        if ($category === ReminderDates::History) {
            return self::history($vault, $kind, $reachesSensitiveFields, $perPage, $page);
        }

        $lastDay = $today->addDays($days);

        $dates = self::until($vault, $today, $lastDay)
            ->filter(fn (ReminderOccurrence $occurrence): bool => $kind === self::All || self::readsABirthday($occurrence->reminder))
            ->map(fn (ReminderOccurrence $occurrence): VaultReminderDate => self::fromOccurrence($occurrence, $reachesSensitiveFields));

        if ($kind === self::Birthdays) {
            $dates = $dates
                ->concat(self::birthdaysWithoutAReminder($vault, $today, $lastDay, $reachesSensitiveFields))
                ->sortBy(fn (VaultReminderDate $date): string => $date->date->toDateString());
        }

        if ($category !== null) {
            $dates = $dates->filter(fn (VaultReminderDate $date): bool => self::categoryOf($date, $today) === $category);
        }

        $dates = $dates->values();

        return new LengthAwarePaginator($dates->forPage($page, $perPage)->values(), $dates->count(), $perPage, $page);
    }

    public static function categoryOf(VaultReminderDate $date, CarbonImmutable $today): string
    {
        $day = $date->date->toDateString();

        if ($day > $today->toDateString()) {
            return ReminderOccurrenceCategoryEnum::Upcoming->value;
        }

        if ($day === $today->toDateString()) {
            return ReminderOccurrenceCategoryEnum::Today->value;
        }

        return ReminderOccurrenceCategoryEnum::Overdue->value;
    }

    /** @return LengthAwarePaginator<int, VaultReminderDate> */
    private static function history(Vault $vault, string $kind, bool $reachesSensitiveFields, int $perPage, int $page): LengthAwarePaginator
    {
        $query = ReminderOccurrence::query()
            ->answeredInVault($vault)
            ->with(['vault', 'reminder' => fn (BelongsTo $rule): BelongsTo => $rule->withTrashed()->with(['remindable', 'field', 'fieldValue.field'])])
            ->orderBy('id');

        if ($kind === self::Birthdays) {
            $query->whereHas('reminder', function (Builder $rule): void {
                $rule->withTrashed()
                    ->where('remindable_type', (new Contact)->getMorphClass())
                    ->whereHas('fieldValue', fn (Builder $value): Builder => $value->where('field_key', DefaultFieldEnum::Birthday->value));
            });
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return new LengthAwarePaginator(
            collect($paginated->items())->map(fn (ReminderOccurrence $occurrence): VaultReminderDate => self::fromOccurrence($occurrence, $reachesSensitiveFields))->all(),
            $paginated->total(),
            $perPage,
            $page,
        );
    }

    private static function fromOccurrence(ReminderOccurrence $occurrence, bool $reachesSensitiveFields): VaultReminderDate
    {
        $rule = $occurrence->reminder;

        return new VaultReminderDate(
            occurrence: $occurrence,
            subject: $rule?->remindable,
            date: $occurrence->local_date,
            age: $rule instanceof Reminder && self::readsABirthday($rule)
                ? self::ageOn($rule->fieldValue, $occurrence->local_date, $reachesSensitiveFields)
                : null,
        );
    }

    private static function readsABirthday(?Reminder $rule): bool
    {
        return $rule?->remindable instanceof Contact
            && $rule->fieldValue?->field_key === DefaultFieldEnum::Birthday->value;
    }

    private static function ageOn(?FieldValue $birthday, CarbonImmutable $date, bool $reachesSensitiveFields): ?int
    {
        if ($birthday?->value_date === null || $birthday->year_is_unknown) {
            return null;
        }

        if ($birthday->field?->is_sensitive && ! $reachesSensitiveFields) {
            return null;
        }

        return $date->year - $birthday->value_date->year;
    }

    /** @return Collection<int, VaultReminderDate> */
    private static function birthdaysWithoutAReminder(Vault $vault, CarbonImmutable $today, CarbonImmutable $lastDay, bool $reachesSensitiveFields): Collection
    {
        return FieldValue::query()
            ->attached()
            ->where('vault_id', $vault->id)
            ->where('account_id', $vault->account_id)
            ->where('valuable_type', (new Contact)->getMorphClass())
            ->whereIn('valuable_id', Contact::query()->where('vault_id', $vault->id)->listed()->select('id'))
            ->where('field_key', DefaultFieldEnum::Birthday->value)
            ->whereNull('field_value_row_id')
            ->whereNotNull('value_date')
            ->where('month_is_unknown', false)
            ->whereDoesntHave('reminder', fn (Builder $rule): Builder => $rule->where('is_active', true))
            ->with(['valuable', 'field'])
            ->get()
            ->reject(fn (FieldValue $birthday): bool => (bool) $birthday->field?->is_sensitive && ! $reachesSensitiveFields)
            ->map(function (FieldValue $birthday) use ($today, $lastDay, $reachesSensitiveFields): ?VaultReminderDate {
                $date = new Birthday(value: $birthday, today: $today)->next();

                if ($date === null || $date->toDateString() > $lastDay->toDateString()) {
                    return null;
                }

                return new VaultReminderDate(
                    occurrence: null,
                    subject: $birthday->valuable instanceof Contact ? $birthday->valuable : null,
                    date: $date,
                    age: self::ageOn($birthday, $date, $reachesSensitiveFields),
                );
            })
            ->filter()
            ->values();
    }
}
