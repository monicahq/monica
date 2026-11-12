<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\FeaturedPossessionOrder;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Feature a possession, placing it last among the featured possessions of its vault, or stop
 * featuring it, closing the order behind it. Nothing else about the possession changes.
 */
class UpdatePossessionFeatured
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly bool $isFeatured,
    ) {}

    public function execute(): Possession
    {
        $this->validate();

        if ($this->possession->is_featured === $this->isFeatured) {
            return $this->possession;
        }

        $this->update();
        $this->log();

        return $this->possession;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }
    }

    private function update(): void
    {
        $this->possession->update([
            'is_featured' => $this->isFeatured,
            'position' => null,
        ]);

        if ($this->isFeatured) {
            FeaturedPossessionOrder::arrange(vaultId: $this->possession->vault_id, moved: $this->possession);

            return;
        }

        FeaturedPossessionOrder::arrange(vaultId: $this->possession->vault_id);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionUpdate,
            parameters: ['name' => $this->possession->name],
            loggable: $this->possession,
        )->onQueue('low');
    }
}
