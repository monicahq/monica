<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionInsuranceRecordAttributes;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Record one policy period covering a possession: the insurer, the status and the insured value in
 * its currency are required, everything else may be added later.
 */
class CreatePossessionInsuranceRecord
{
    private PossessionInsuranceRecord $record;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly string $insurer,
        private readonly PossessionInsuranceStatusEnum $status,
        private readonly string $insuredValue,
        private readonly CurrencyEnum $currency,
        private readonly ?string $policyNumber = null,
        private readonly ?string $coverage = null,
        private readonly ?string $excess = null,
        private readonly ?CarbonImmutable $startsOn = null,
        private readonly ?CarbonImmutable $endsOn = null,
        private readonly bool $isListedIndividually = false,
        private readonly ?string $contactName = null,
        private readonly ?string $contactEmail = null,
        private readonly ?string $contactPhone = null,
        private readonly ?string $note = null,
    ) {}

    public function execute(): PossessionInsuranceRecord
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->record;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }
    }

    private function create(): void
    {
        $attributes = PossessionInsuranceRecordAttributes::from(
            insurer: $this->insurer,
            status: $this->status,
            insuredValue: $this->insuredValue,
            currency: $this->currency,
            policyNumber: $this->policyNumber,
            coverage: $this->coverage,
            excess: $this->excess,
            startsOn: $this->startsOn,
            endsOn: $this->endsOn,
            isListedIndividually: $this->isListedIndividually,
            contactName: $this->contactName,
            contactEmail: $this->contactEmail,
            contactPhone: $this->contactPhone,
            note: $this->note,
        );

        $this->record = PossessionInsuranceRecord::query()->create([
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
            action: UserActionEnum::PossessionInsuranceRecordCreation,
            parameters: ['name' => $this->possession->name],
            loggable: $this->record,
        )->onQueue('low');
    }
}
