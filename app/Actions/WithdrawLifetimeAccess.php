<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessEndReasonEnum;
use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\StripeOrder;
use App\Models\User;

/**
 * Take the full product back off an account, whether it was granted or bought.
 *
 * A grant is simply undated. A purchase keeps its order, which is marked with what happened to the
 * money: an administrator withdraws a purchase because it was given back, so the order reads
 * refunded unless the caller says it was charged back. Nothing is deleted and the owner is not
 * emailed, since a person is already dealing with them.
 */
class WithdrawLifetimeAccess
{
    public function __construct(
        private readonly Account $account,
        private readonly User $administrator,
        private readonly AccessEndReasonEnum $reason = AccessEndReasonEnum::Refunded,
    ) {}

    public function execute(): Account
    {
        $this->withdrawTheGrant();
        $this->endTheOrder();
        $this->log();

        return $this->account;
    }

    private function withdrawTheGrant(): void
    {
        if ($this->account->access_granted_at === null) {
            return;
        }

        $this->account->access_granted_at = null;
        $this->account->save();
    }

    private function endTheOrder(): void
    {
        $order = $this->account->settledOrder();

        if ($order === null) {
            return;
        }

        $order->stripe_status = $this->reason === AccessEndReasonEnum::Disputed
            ? StripeOrder::DISPUTED
            : StripeOrder::REFUNDED;
        $order->save();

        $this->account->unsetRelation('orders');
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::LifetimeAccessWithdrawn,
            account: $this->account,
            parameters: ['name' => $this->account->ownerName()],
            subject: $this->account,
        )->onQueue('low');
    }
}
