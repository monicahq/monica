<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionLoanAttributes;
use App\Helpers\PossessionLoanProvenance;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Record a possession lent to a contact of its vault, or borrowed from one, without any change of
 * owner. The contact is either one already recorded or a new one written down with the loan, in which
 * case both are saved together or neither is.
 */
class CreatePossessionLoan
{
    private PossessionLoan $loan;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly PossessionLoanDirectionEnum $direction,
        private readonly PossessionLoanStatusEnum $status,
        private readonly CarbonImmutable $startedOn,
        private readonly ?Contact $contact = null,
        private readonly ?string $newContactFirstName = null,
        private readonly ?string $newContactLastName = null,
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
        $this->create();
        $this->log();

        return $this->loan;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }

        if ($this->contact instanceof Contact) {
            PossessionLoanAttributes::ensureContactOfTheVault($this->possession, $this->contact);

            return;
        }

        if ($this->newContactFirstName === null) {
            throw ValidationException::withMessages(['contact_id' => __('Choose a contact, or create a new one.')]);
        }

        if (TextSanitizer::plainText($this->newContactFirstName) === '') {
            throw ValidationException::withMessages(['new_contact_first_name' => __('Give the new contact at least a first name.')]);
        }
    }

    private function create(): void
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

        DB::transaction(function () use ($attributes): void {
            Possession::query()->whereKey($this->possession->id)->lockForUpdate()->first();

            PossessionLoanAttributes::refuseASecondActiveLoan($this->possession, $this->status);

            $contact = $this->contact ?? new CreateContact(
                user: $this->user,
                vault: $this->possession->vault,
                firstName: $this->newContactFirstName,
                lastName: $this->newContactLastName,
            )->execute();

            $this->loan = PossessionLoan::query()->create([
                'account_id' => $this->possession->account_id,
                'vault_id' => $this->possession->vault_id,
                'possession_id' => $this->possession->id,
                'contact_id' => $contact->id,
                ...$attributes,
            ]);

            PossessionLoanProvenance::synchronise($this->loan);
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionLoanCreation,
            parameters: ['name' => $this->possession->name],
            loggable: $this->loan,
        )->onQueue('low');
    }
}
