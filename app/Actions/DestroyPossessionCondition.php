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
 * Delete a possession condition for good and close the gap it leaves in the order. The maintenance
 * records naming it lose that side, and nothing else goes with it.
 */
class DestroyPossessionCondition
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionCondition $possessionCondition,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->possessionCondition->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }
    }

    private function destroy(): void
    {
        DB::transaction(function (): void {
            $this->possessionCondition->delete();

            $this->user->account->possessionConditions()
                ->get()
                ->each(function (PossessionCondition $possessionCondition, int $index): void {
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
            action: UserActionEnum::PossessionConditionDeletion,
            parameters: ['name' => $this->possessionCondition->name],
            loggable: $this->possessionCondition,
        )->onQueue('low');
    }
}
