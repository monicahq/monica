<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionLoanAttributes;
use App\Helpers\PossessionLoanProvenance;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Change a loan of a possession, including marking it as returned. A loan that is not returned loses
 * its return day and the condition it came back in, and its provenance events follow what it now
 * says. Leaving the contact out keeps the one it has, deleted or not.
 */
class UpdatePossessionLoan
{
    private bool $isReturnedNow = false;

    public function __construct(
        private readonly User $user,
        private readonly PossessionLoan $loan,
        private readonly PossessionLoanDirectionEnum $direction,
        private readonly PossessionLoanStatusEnum $status,
        private readonly CarbonImmutable $startedOn,
        private readonly ?Contact $contact = null,
        private readonly ?CarbonImmutable $dueOn = null,
        private readonly ?CarbonImmutable $returnedOn = null,
        private readonly ?PossessionCondition $conditionOut = null,
        private readonly ?PossessionCondition $conditionBack = null,
        private readonly ?string $depositAmount = null,
        private readonly ?CurrencyEnum $depositCurrency = null,
        private readonly ?string $purpose = null,
        private readonly bool $isInProvenance = false,
    ) {}

    public function execute(): PossessionLoan
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->loan;
    }

    private function validate(): void
    {
        if ($this->loan->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This loan is in another account.');
        }

        if ($this->contact instanceof Contact) {
            PossessionLoanAttributes::ensureContactOfTheVault($this->loan->possession, $this->contact);
        }
    }

    private function update(): void
    {
        $attributes = PossessionLoanAttributes::from(
            user: $this->user,
            direction: $this->direction,
            status: $this->status,
            startedOn: $this->startedOn,
            dueOn: $this->dueOn,
            returnedOn: $this->returnedOn,
            conditionOut: $this->conditionOut,
            conditionBack: $this->conditionBack,
            depositAmount: $this->depositAmount,
            depositCurrency: $this->depositCurrency,
            purpose: $this->purpose,
            isInProvenance: $this->isInProvenance,
        );

        $this->isReturnedNow = $this->loan->status !== PossessionLoanStatusEnum::Returned
            && $this->status === PossessionLoanStatusEnum::Returned;

        DB::transaction(function () use ($attributes): void {
            Possession::query()->whereKey($this->loan->possession_id)->lockForUpdate()->first();

            PossessionLoanAttributes::refuseASecondActiveLoan($this->loan->possession, $this->status, $this->loan);

            $this->loan->fill([
                'contact_id' => $this->contact instanceof Contact ? $this->contact->id : $this->loan->contact_id,
                ...$attributes,
            ]);
            $this->loan->save();
            $this->loan->unsetRelation('contact');

            PossessionLoanProvenance::synchronise($this->loan);
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $this->isReturnedNow ? UserActionEnum::PossessionLoanReturn : UserActionEnum::PossessionLoanUpdate,
            parameters: ['name' => $this->loan->possession->name],
            loggable: $this->loan,
        )->onQueue('low');
    }
}
