<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionFieldGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a group to another place among the groups of its possession type. Every group is numbered
 * again from one.
 */
class UpdatePossessionFieldGroupPosition
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionFieldGroup $group,
        private readonly int $position,
    ) {}

    public function execute(): PossessionFieldGroup
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->group;
    }

    private function validate(): void
    {
        if ($this->group->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This group is in another account.');
        }
    }

    private function update(): void
    {
        $groups = PossessionFieldGroup::query()
            ->where('possession_type_id', $this->group->possession_type_id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->reject(fn (PossessionFieldGroup $group): bool => $group->is($this->group))
            ->values();

        $groups->splice(max(0, $this->position - 1), 0, [$this->group]);

        DB::transaction(function () use ($groups): void {
            $groups->each(function (PossessionFieldGroup $group, int $index): void {
                $group->position = $index + 1;
                $group->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldGroupUpdate,
            parameters: ['name' => $this->group->name, 'type' => $this->group->possessionType->name],
            loggable: $this->group,
        )->onQueue('low');
    }
}
