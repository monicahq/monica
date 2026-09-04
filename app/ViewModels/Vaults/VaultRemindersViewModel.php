<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What one vault will still remind somebody about.
 *
 * A row here is a deadline, not a rule, because three of the four things a row
 * offers act on one concrete instant. That has a consequence worth stating: a
 * rule whose next date lies beyond the window deadlines are generated for has no
 * row at all, and a yearly birthday sits outside a ninety day window for most of
 * the year. So the screen says which stretch of time it covers, and its empty
 * state tells "nothing due soon" apart from "nothing set up". Without that, a
 * short list reads as data loss.
 *
 * The buckets are worked out here rather than by the database, because the suite
 * runs on three engines and date arithmetic differs across them. One vault's
 * ninety days is a few dozen rows, so the sorting the query already did is
 * enough and the grouping costs nothing.
 *
 * Every boundary is measured from a single instant, taken once. Asking `now()`
 * per bucket is how a deadline falls into two of them, or into none.
 */
class VaultRemindersViewModel
{
    public const string GroupByWhen = 'when';

    public const string GroupByContact = 'contact';

    /** @var Collection<int, ReminderRow>|null */
    private ?Collection $rows = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $groupBy = self::GroupByWhen,
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

    /**
     * The line under the title, which has to be true about three things at once:
     * how a reminder arrives, when it arrives, and how far ahead this list looks.
     */
    public function intro(): string
    {
        return implode(' ', [
            trans_choice(
                'Everything this vault will remind you about in the next :count day, and everything it already has.|Everything this vault will remind you about in the next :count days, and everything it already has.',
                $this->horizonInDays(),
            ),
            __('A reminder reaches you in the app and by email, at the hour its own rule sets.'),
        ]);
    }

    public function groupBy(): string
    {
        return $this->groupBy === self::GroupByContact ? self::GroupByContact : self::GroupByWhen;
    }

    public function activeUrl(): string
    {
        return route('reminders.index', $this->vault);
    }

    public function groupedByWhenUrl(): string
    {
        return route('reminders.index', $this->vault);
    }

    public function groupedByContactUrl(): string
    {
        return route('reminders.byContact', $this->vault);
    }

    public function sentUrl(): string
    {
        return route('reminders.sent', $this->vault);
    }

    public function activeCount(): int
    {
        return $this->rows()->count();
    }

    /**
     * How many deadlines have already gone out, for the tab beside this one. It
     * is counted rather than listed, since this screen never draws them.
     */
    public function sentCount(): int
    {
        return ReminderOccurrence::query()->sentInVault($this->vault)->count();
    }

    /**
     * The list, in the order it is drawn. A bucket holding nothing is left out
     * rather than drawn empty: five headings over one reminder says the screen is
     * mostly missing.
     *
     * @return array<int, array{title: string, count: string, isOverdue: bool, rows: array<int, array<string, mixed>>}>
     */
    public function groups(): array
    {
        if ($this->groupBy() === self::GroupByContact) {
            return $this->byContact();
        }

        return $this->byWhen();
    }

    /**
     * Whether this vault has any live rule at all.
     *
     * Asked only when the list came back empty, and it is what tells the two
     * empty states apart.
     */
    public function hasAnyRule(): bool
    {
        return Reminder::query()
            ->where('vault_id', $this->vault->id)
            ->where('account_id', $this->vault->account_id)
            ->active()
            ->exists();
    }

    public function emptyTitle(): string
    {
        return $this->hasAnyRule()
            ? __('Nothing due just yet')
            : __('No reminders in this vault');
    }

    public function emptyLine(): string
    {
        if ($this->hasAnyRule()) {
            return trans_choice(
                'This vault has reminders, and none of them falls in the next :count day. They will appear here as their dates come closer.|This vault has reminders, and none of them falls in the next :count days. They will appear here as their dates come closer.',
                $this->horizonInDays(),
            );
        }

        return __('A reminder is built from a date on somebody: open a contact, find a date on their page, and ask to be reminded of it.');
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    /**
     * @return array<int, array{title: string, count: string, isOverdue: bool, rows: array<int, array<string, mixed>>}>
     */
    private function byWhen(): array
    {
        $now = CarbonImmutable::now($this->user->timezone);

        $boundaries = [
            __('This week') => $now->endOfWeek(),
            __('Later this month') => $now->endOfMonth(),
            __('Next month') => $now->addMonthNoOverflow()->endOfMonth(),
        ];

        $buckets = [__('Overdue') => [], ...array_map(fn (): array => [], $boundaries), __('Later') => []];

        foreach ($this->rows() as $row) {
            $buckets[$this->bucketFor($row, $now, $boundaries)][] = $row->get();
        }

        $groups = [];

        foreach ($buckets as $title => $rows) {
            if ($rows === []) {
                continue;
            }

            $groups[] = [
                'title' => $title,
                'count' => $this->countLine(count($rows)),
                'isOverdue' => $title === __('Overdue'),
                'rows' => $rows,
            ];
        }

        return $groups;
    }

    /**
     * Which bucket one deadline belongs in. First match wins, so a deadline can
     * never be in two, and the order of the boundaries is the order of the list.
     *
     * @param  array<string, CarbonImmutable>  $boundaries
     */
    private function bucketFor(ReminderRow $row, CarbonImmutable $now, array $boundaries): string
    {
        if ($row->dueAt()->lessThan($now)) {
            return __('Overdue');
        }

        foreach ($boundaries as $title => $end) {
            if ($row->dueAt()->lessThanOrEqualTo($end)) {
                return $title;
            }
        }

        return __('Later');
    }

    /**
     * @return array<int, array{title: string, count: string, isOverdue: bool, rows: array<int, array<string, mixed>>}>
     */
    private function byContact(): array
    {
        /* Named before ordering, because the collator returns a list and the
           name would otherwise be gone by the time the group is built. */
        return $this->rows()
            ->groupBy(fn (ReminderRow $row): string => $row->contactName() ?? $this->nobody())
            ->map(fn (Collection $rows, string $name): array => [
                'title' => $name,
                'count' => $this->countLine($rows->count()),
                'isOverdue' => false,
                'rows' => $rows->map(fn (ReminderRow $row): array => $row->get())->all(),
            ])
            ->sortByCollator('title')
            ->all();
    }

    private function countLine(int $count): string
    {
        return trans_choice(':count reminder|:count reminders', $count);
    }

    /**
     * Every deadline the screen draws, read once.
     *
     * The rule, the record it is about, that record's avatar and the field it
     * reads are all loaded with it, because every row asks for all four and the
     * number of queries must not follow the number of rows.
     *
     * @return Collection<int, ReminderRow>
     */
    private function rows(): Collection
    {
        if ($this->rows instanceof Collection) {
            return $this->rows;
        }

        return $this->rows = ReminderOccurrence::query()
            ->upcomingInVault(
                vault: $this->vault,
                until: CarbonImmutable::now()->addDays($this->horizonInDays()),
            )
            ->with(['reminder.remindable.avatar', 'reminder.field'])
            ->get()
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ));
    }

    /**
     * What a row is grouped under when the rule it came from has been taken away
     * and there is no person left to name.
     */
    private function nobody(): string
    {
        return __('Somebody who is gone');
    }

    private function horizonInDays(): int
    {
        return (int) config('monica.reminder_generation_window_in_days');
    }
}
