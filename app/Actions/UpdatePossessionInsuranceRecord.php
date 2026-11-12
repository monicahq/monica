<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionInsuranceRecordAttributes;
use App\Jobs\LogUserAction;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what one insurance record of a possession says, under the rules that recorded it. The other
 * records are not touched.
 */
class UpdatePossessionInsuranceRecord
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionInsuranceRecord $record,
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
        $this->update();
        $this->log();

        return $this->record;
    }

    private function validate(): void
    {
        if ($this->record->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This insurance record is in another account.');
        }
    }

    private function update(): void
    {
        $this->record->update(PossessionInsuranceRecordAttributes::from(
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
        ));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionInsuranceRecordUpdate,
            parameters: ['name' => $this->record->possession->name],
            loggable: $this->record,
        )->onQueue('low');
    }
}
