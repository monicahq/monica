<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\FeaturedPossessionOrder;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\PossessionTransaction;
use App\Models\PossessionType;
use App\Models\PossessionValuation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a possession type with its groups, its fields, and every possession of that type with the
 * values it held, their transactions and their valuations, whose documents are detached so they
 * wait out the grace period. How many possessions went is written to the log and returned, so that
 * the deletion is never silent, and the featured order of every vault that held one closes behind
 * them.
 */
class DestroyPossessionType
{
    private string $name;

    private int $possessionCount;

    /** @var list<int> */
    private array $vaultIds;

    public function __construct(
        private readonly User $user,
        private readonly PossessionType $possessionType,
    ) {}

    public function execute(): int
    {
        $this->validate();
        $this->destroy();
        $this->log();

        return $this->possessionCount;
    }

    private function validate(): void
    {
        if ($this->possessionType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession type is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->possessionType->name;
        $this->possessionCount = $this->possessionType->possessions()->count();
        $this->vaultIds = $this->possessionType->possessions()
            ->where('is_featured', true)
            ->distinct()
            ->pluck('vault_id')
            ->all();

        DB::transaction(function (): void {
            PossessionTransaction::query()
                ->whereIn('possession_id', $this->possessionType->possessions()->select('id'))
                ->with('documents')
                ->get()
                ->flatMap(fn (PossessionTransaction $transaction): Collection => $transaction->documents)
                ->each(fn (File $file) => new DetachFile(user: $this->user, file: $file)->execute());

            PossessionValuation::query()
                ->whereIn('possession_id', $this->possessionType->possessions()->select('id'))
                ->with('documents')
                ->get()
                ->flatMap(fn (PossessionValuation $valuation): Collection => $valuation->documents)
                ->each(fn (File $file) => new DetachFile(user: $this->user, file: $file)->execute());

            $this->possessionType->delete();
        });

        foreach ($this->vaultIds as $vaultId) {
            FeaturedPossessionOrder::arrange(vaultId: $vaultId);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTypeDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->possessionType,
        )->onQueue('low');

        if ($this->possessionCount === 0) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionsDeletedWithType,
            parameters: ['name' => $this->name, 'count' => $this->possessionCount],
            loggable: $this->possessionType,
        )->onQueue('low');
    }
}
