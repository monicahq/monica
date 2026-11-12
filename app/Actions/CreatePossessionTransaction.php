<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionTransactionAttributes;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Record money or ownership changing hands for a possession. Every figure is zero or more, and a
 * figure needs the currency it is in; nothing else is required beyond the type.
 */
class CreatePossessionTransaction
{
    private PossessionTransaction $transaction;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
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
        $this->create();
        $this->log();

        return $this->transaction;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }
    }

    private function create(): void
    {
        $attributes = PossessionTransactionAttributes::from(
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
        );

        $this->transaction = PossessionTransaction::query()->create([
            'account_id' => $this->possession->account_id,
            'vault_id' => $this->possession->vault_id,
            'possession_id' => $this->possession->id,
            ...$attributes,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTransactionCreation,
            parameters: ['name' => $this->possession->name],
            loggable: $this->transaction,
        )->onQueue('low');
    }
}
