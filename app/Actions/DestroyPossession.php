<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\FeaturedPossessionOrder;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a possession for good, with the values it held, its insurance records, its transactions, its
 * provenance events and its valuations, whose documents are detached so they wait out the grace period. A featured one leaves the order, which closes behind it.
 */
class DestroyPossession
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }
    }

    private function destroy(): void
    {
        DB::transaction(function (): void {
            $this->possession->transactions()->with('documents')->get()
                ->flatMap(fn (PossessionTransaction $transaction): Collection => $transaction->documents)
                ->each(fn (File $file) => new DetachFile(user: $this->user, file: $file)->execute());

            $this->possession->valuations()->with('documents')->get()
                ->flatMap(fn (PossessionValuation $valuation): Collection => $valuation->documents)
                ->each(fn (File $file) => new DetachFile(user: $this->user, file: $file)->execute());

            $this->possession->delete();
        });

        if ($this->possession->is_featured) {
            FeaturedPossessionOrder::arrange(vaultId: $this->possession->vault_id);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionDeletion,
            parameters: ['name' => $this->possession->name],
            loggable: $this->possession,
        )->onQueue('low');
    }
}
