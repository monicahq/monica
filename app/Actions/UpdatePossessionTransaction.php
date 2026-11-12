<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionTransactionAttributes;
use App\Jobs\LogUserAction;
use App\Models\PossessionTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what a transaction of a possession says, under the rules that recorded it. The acquisition
 * date and price follow, since they are read from the transactions.
 */
class UpdatePossessionTransaction
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionTransaction $transaction,
        private readonly PossessionTransactionTypeEnum $type,
        private readonly ?CarbonImmutable $occurredOn = null,
        private readonly ?string $counterparty = null,
        private readonly ?string $amount = null,
        private readonly ?string $tax = null,
        private readonly ?string $fees = null,
        private readonly ?string $shipping = null,
        private readonly ?string $enteredTotal = null,
        private readonly ?CurrencyEnum $currency = null,
        private readonly ?string $reference = null,
        private readonly ?string $sourceUrl = null,
        private readonly ?string $note = null,
    ) {}

    public function execute(): PossessionTransaction
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->transaction;
    }

    private function validate(): void
    {
        if ($this->transaction->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This transaction is in another account.');
        }
    }

    private function update(): void
    {
        $this->transaction->update(PossessionTransactionAttributes::from(
            type: $this->type,
            occurredOn: $this->occurredOn,
            counterparty: $this->counterparty,
            figures: [
                'amount' => $this->amount,
                'tax' => $this->tax,
                'fees' => $this->fees,
                'shipping' => $this->shipping,
                'entered_total' => $this->enteredTotal,
            ],
            currency: $this->currency,
            reference: $this->reference,
            sourceUrl: $this->sourceUrl,
            note: $this->note,
        ));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTransactionUpdate,
            parameters: ['name' => $this->transaction->possession->name],
            loggable: $this->transaction,
        )->onQueue('low');
    }
}
