<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;

class GroupRemindersBlock
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
    ) {}

    /**
     * Each live reminder about the group, by its next deadline still waiting for an answer, the
     * soonest first. A reminder with nothing left to wait for is left out.
     *
     * @return list<array{row: ReminderRow, repeats: bool}>
     */
    public function rows(): array
    {
        return once(function (): array {
            $rules = Reminder::query()
                ->where('account_id', $this->user->account_id)
                ->where('remindable_type', $this->group->getMorphClass())
                ->where('remindable_id', $this->group->id)
                ->where('is_active', true)
                ->get()
                ->each(function (Reminder $rule): void {
                    $rule->setRelation('remindable', $this->group);
                    $rule->setRelation('vault', $this->vault);
                })
                ->keyBy('id');

            if ($rules->isEmpty()) {
                return [];
            }

            return ReminderOccurrence::query()
                ->whereIn('reminder_id', $rules->keys())
                ->active()
                ->orderBy('scheduled_for')
                ->get()
                ->unique('reminder_id')
                ->map(function (ReminderOccurrence $occurrence) use ($rules): array {
                    $rule = $rules[$occurrence->reminder_id];
                    $occurrence->setRelation('reminder', $rule);
                    $occurrence->setRelation('vault', $this->vault);

                    return [
                        'row' => new ReminderRow(user: $this->user, occurrence: $occurrence),
                        'repeats' => $rule->cadence !== ReminderCadenceEnum::Once,
                    ];
                })
                ->values()
                ->all();
        });
    }

    public function holdsNothing(): bool
    {
        return $this->rows() === [];
    }

    public function newUrl(): string
    {
        return route('groups.reminders.new', ['vault' => $this->vault, 'group' => $this->group]);
    }
}
