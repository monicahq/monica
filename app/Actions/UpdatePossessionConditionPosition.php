<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a possession condition in the account's order, so the list keeps reading from best to worst.
 * A position past the end puts it last.
 */
class UpdatePossessionConditionPosition
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionCondition $possessionCondition,
        private readonly int $position,
    ) {}

    public function execute(): PossessionCondition
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->possessionCondition->refresh();
    }

    private function validate(): void
    {
        if ($this->possessionCondition->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }
    }

    private function update(): void
    {
        $possessionConditions = $this->user->account->possessionConditions()
            ->get()
            ->reject(fn (PossessionCondition $possessionCondition): bool => $possessionCondition->is($this->possessionCondition))
            ->values();

        $possessionConditions->splice(max(0, $this->position - 1), 0, [$this->possessionCondition]);

        DB::transaction(function () use ($possessionConditions): void {
            $possessionConditions->each(function (PossessionCondition $possessionCondition, int $index): void {
                $possessionCondition->position = $index + 1;
                $possessionCondition->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionConditionPositionUpdate,
            parameters: ['name' => $this->possessionCondition->name],
            loggable: $this->possessionCondition,
        )->onQueue('low');
    }
}
