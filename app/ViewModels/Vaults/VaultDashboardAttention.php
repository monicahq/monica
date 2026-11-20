<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;

class VaultDashboardAttention
{
    /** @var list<array{title: string, icon: string, tone: string, isOverdue: bool, rows: list<ReminderRow>}>|null */
    private ?array $bands = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    /** @return list<array{title: string, icon: string, tone: string, isOverdue: bool, rows: list<ReminderRow>}> */
    public function bands(): array
    {
        if ($this->bands !== null) {
            return $this->bands;
        }

        $today = CarbonImmutable::now($this->user->timezone)->startOfDay();

        $rows = [
            ReminderOccurrenceCategoryEnum::Overdue->value => [],
            ReminderOccurrenceCategoryEnum::Today->value => [],
            ReminderOccurrenceCategoryEnum::Upcoming->value => [],
        ];

        foreach (new VaultDeadlines(user: $this->user, vault: $this->vault)->rowsUntil($today->addDays(ReminderOccurrenceCategoryEnum::UpcomingDays)) as $row) {
            $rows[ReminderOccurrenceCategoryEnum::for($row->occurrence(), $today)->value][] = $row;
        }

        $headings = [
            ReminderOccurrenceCategoryEnum::Overdue->value => ['title' => __('Overdue'), 'icon' => 'clock', 'tone' => 'error'],
            ReminderOccurrenceCategoryEnum::Today->value => ['title' => __('Today'), 'icon' => 'sun', 'tone' => 'warning'],
            ReminderOccurrenceCategoryEnum::Upcoming->value => ['title' => __('Next seven days'), 'icon' => 'calendar', 'tone' => 'default'],
        ];

        $bands = [];

        foreach ($rows as $category => $inBand) {
            if ($inBand === []) {
                continue;
            }

            $bands[] = [
                'title' => $headings[$category]['title'],
                'icon' => $headings[$category]['icon'],
                'tone' => $headings[$category]['tone'],
                'isOverdue' => $category === ReminderOccurrenceCategoryEnum::Overdue->value,
                'rows' => $inBand,
            ];
        }

        return $this->bands = $bands;
    }

    public function count(): int
    {
        return array_sum(array_map(fn (array $band): int => count($band['rows']), $this->bands()));
    }

    public function isEmpty(): bool
    {
        return $this->bands() === [];
    }

    public function remindersUrl(): string
    {
        return route('reminders.index', $this->vault);
    }
}
