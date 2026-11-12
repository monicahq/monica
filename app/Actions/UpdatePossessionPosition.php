<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\FeaturedPossessionOrder;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Move a featured possession to another place among the featured possessions of its vault. Every
 * one of them is numbered again from one.
 */
class UpdatePossessionPosition
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly int $position,
    ) {}

    public function execute(): Possession
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->possession;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }

        if (! $this->possession->is_featured) {
            throw ValidationException::withMessages([
                'position' => __('Only a featured possession has a place in the order.'),
            ]);
        }
    }

    private function update(): void
    {
        FeaturedPossessionOrder::arrange(
            vaultId: $this->possession->vault_id,
            moved: $this->possession,
            position: $this->position,
        );
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
