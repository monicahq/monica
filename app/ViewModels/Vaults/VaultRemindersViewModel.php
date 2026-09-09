<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

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

    public function sentCount(): int
    {
        return ReminderOccurrence::query()->sentInVault($this->vault)->count();
    }

    /**
     * @return array<int, array{title: string, count: string, isOverdue: bool, rows: list<ReminderRow>}>
     */
    public function groups(): array
    {
        if ($this->groupBy() === self::GroupByContact) {
            return $this->byContact();
        }

        return $this->byWhen();
    }

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
            ? __('Nothing due yet')
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

        return __('A reminder is built from a date on a contact. Open a contact, find a date on their page, and ask to be reminded of it.');
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    /**
     * @return array<int, array{title: string, count: string, isOverdue: bool, rows: list<ReminderRow>}>
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
            $buckets[$this->bucketFor($row, $now, $boundaries)][] = $row;
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

    /** @param  array<string, CarbonImmutable>  $boundaries */
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
     * @return array<int, array{title: string, count: string, isOverdue: bool, rows: list<ReminderRow>}>
     */
    private function byContact(): array
    {
        return $this->rows()
            ->groupBy(fn (ReminderRow $row): string => $row->contactName() ?? $this->nobody())
            ->map(fn (Collection $rows, string $name): array => [
                'title' => $name,
                'count' => $this->countLine($rows->count()),
                'isOverdue' => false,
                'rows' => $rows->values()->all(),
            ])
            ->sortByCollator('title')
            ->all();
    }

    private function countLine(int $count): string
    {
        return trans_choice(':count reminder|:count reminders', $count);
    }

    /** @return Collection<int, ReminderRow> */
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
            ->with(['vault', 'reminder.remindable.avatar', 'reminder.field'])
            ->get()
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ));
    }

    private function nobody(): string
    {
        return __('A deleted contact');
    }

    private function horizonInDays(): int
    {
        return (int) config('monica.reminder_generation_window_in_days');
    }
}
