<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class VaultRemindersViewModel
{
    public const string GROUP_BY_WHEN = 'when';

    public const string GROUP_BY_CONTACT = 'contact';

    public const int PER_PAGE = 50;

    /** @var Collection<int, ReminderRow>|null */
    private ?Collection $rows = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $groupBy = self::GROUP_BY_WHEN,
        private readonly int $page = 1,
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
        return $this->groupBy === self::GROUP_BY_CONTACT ? self::GROUP_BY_CONTACT : self::GROUP_BY_WHEN;
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
     * The groups holding the rows of this page. A later page carries on the list where the previous
     * one stopped, so a group the previous page was already in does not get its heading twice.
     *
     * @return array<int, array{title: string, count: string, isOverdue: bool, showsHeading: bool, rows: list<ReminderRow>}>
     */
    public function groups(): array
    {
        $groups = $this->groupBy() === self::GROUP_BY_CONTACT ? $this->byContact() : $this->byWhen();
        $start = ($this->page() - 1) * self::PER_PAGE;
        $end = $start + self::PER_PAGE;
        $position = 0;
        $onThisPage = [];

        foreach ($groups as $group) {
            $first = $position;
            $position += count($group['rows']);

            if ($position <= $start || $first >= $end) {
                continue;
            }

            $onThisPage[] = [
                ...$group,
                'showsHeading' => $first >= $start,
                'rows' => array_slice($group['rows'], max($start - $first, 0), $end - max($first, $start)),
            ];
        }

        return $onThisPage;
    }

    public function holdsNothing(): bool
    {
        return $this->rows()->isEmpty();
    }

    public function nextPageUrl(): ?string
    {
        if ($this->page() * self::PER_PAGE >= $this->rows()->count()) {
            return null;
        }

        $route = $this->groupBy() === self::GROUP_BY_CONTACT ? 'reminders.byContact' : 'reminders.index';

        return route($route, ['vault' => $this->vault, 'page' => $this->page() + 1]);
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
     * @return array<string, array{title: string, count: string, isOverdue: bool, rows: list<ReminderRow>}>
     */
    private function byContact(): array
    {
        return $this->rows()
            ->groupBy(fn (ReminderRow $row): string => $row->recordName() === null ? '' : $row->occurrence()->reminder?->remindable_type.':'.$row->occurrence()->reminder?->remindable_id)
            ->map(fn (Collection $rows): array => [
                'title' => $rows->first()->recordName() ?? $this->nobody(),
                'count' => $this->countLine($rows->count()),
                'isOverdue' => false,
                'rows' => $rows->values()->all(),
            ])
            ->sortByCollator('title')
            ->all();
    }

    private function page(): int
    {
        return max($this->page, 1);
    }

    private function countLine(int $count): string
    {
        return trans_choice(':count reminder|:count reminders', $count);
    }

    /** @return Collection<int, ReminderRow> */
    private function rows(): Collection
    {
        return $this->rows ??= new VaultDeadlines(user: $this->user, vault: $this->vault)->rows();
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
