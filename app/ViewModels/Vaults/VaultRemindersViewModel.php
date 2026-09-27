<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Helpers\NextReminderDeadlines;
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

    public function historyUrl(): string
    {
        return route('reminders.history', $this->vault);
    }

    public function historyCount(): int
    {
        return ReminderOccurrence::query()->answeredInVault($this->vault)->count();
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
            return __('This vault has reminders, and none of them has a date coming.');
        }

        return __('Add a reminder from a contact’s page, or ask to be told about a date on it, such as a birthday.');
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
        $today = $this->today();
        $buckets = [
            ReminderOccurrenceCategoryEnum::Overdue->value => [],
            ReminderOccurrenceCategoryEnum::Today->value => [],
            ReminderOccurrenceCategoryEnum::Upcoming->value => [],
        ];

        foreach ($this->rows() as $row) {
            $buckets[ReminderOccurrenceCategoryEnum::for($row->occurrence(), $today)->value][] = $row;
        }

        $titles = [
            ReminderOccurrenceCategoryEnum::Overdue->value => __('Overdue'),
            ReminderOccurrenceCategoryEnum::Today->value => __('Today'),
            ReminderOccurrenceCategoryEnum::Upcoming->value => __('Upcoming'),
        ];

        $groups = [];

        foreach ($buckets as $category => $rows) {
            if ($rows === []) {
                continue;
            }

            $groups[] = [
                'title' => $titles[$category],
                'count' => $this->countLine(count($rows)),
                'isOverdue' => $category === ReminderOccurrenceCategoryEnum::Overdue->value,
                'rows' => $rows,
            ];
        }

        return $groups;
    }

    /**
     * @return array<int, array{title: string, count: string, isOverdue: bool, rows: list<ReminderRow>}>
     */
    private function byContact(): array
    {
        return $this->rows()
            ->groupBy(fn (ReminderRow $row): int => $row->contactName() === null ? 0 : (int) $row->occurrence()->reminder?->remindable_id)
            ->map(fn (Collection $rows): array => [
                'title' => $rows->first()->contactName() ?? $this->nobody(),
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

        $waiting = ReminderOccurrence::query()
            ->awaitingAnswerInVault(vault: $this->vault, untilDay: $this->today())
            ->with(['vault', 'reminder.remindable.avatar', 'reminder.field'])
            ->get();

        $rules = Reminder::query()
            ->where('vault_id', $this->vault->id)
            ->where('account_id', $this->vault->account_id)
            ->active()
            ->with(['vault', 'remindable.avatar', 'field', 'fieldValue'])
            ->get();

        return $this->rows = $waiting
            ->concat(NextReminderDeadlines::for($rules, $this->today()))
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ))
            ->values();
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->user->timezone)->startOfDay();
    }

    private function nobody(): string
    {
        return __('A deleted contact');
    }
}
