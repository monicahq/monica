<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\PossessionValuation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a valuation of a possession for good. Its documents are detached rather than deleted on
 * the spot, so they wait out the grace period every file gets before the sweep removes it.
 */
class DestroyPossessionValuation
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionValuation $valuation,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->valuation->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This valuation is in another account.');
        }
    }

    private function destroy(): void
    {
        DB::transaction(function (): void {
            $this->valuation->documents->each(
                fn (File $file) => new DetachFile(user: $this->user, file: $file)->execute(),
            );

            $this->valuation->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionValuationDeletion,
            parameters: ['name' => $this->valuation->possession->name],
            loggable: $this->valuation,
        )->onQueue('low');
    }
}
