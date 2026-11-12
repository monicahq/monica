<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionDeclaredValue;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Record what a possession is worth on a date. The type, the date and the amount in its currency are
 * required. A valuation is never changed afterwards: a correction is recorded as a new valuation.
 */
class CreatePossessionValuation
{
    private PossessionValuation $valuation;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly PossessionValuationTypeEnum $type,
        private readonly CarbonImmutable $valuedOn,
        private readonly string $amount,
        private readonly CurrencyEnum $currency,
        private readonly PossessionValuationConfidenceEnum $confidence = PossessionValuationConfidenceEnum::Unknown,
        private readonly ?string $valuer = null,
        private readonly ?string $method = null,
        private readonly ?string $referenceNumber = null,
        private readonly ?string $sourceUrl = null,
        private readonly ?string $note = null,
    ) {}

    public function execute(): PossessionValuation
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->valuation;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }

        $amount = mb_trim($this->amount);

        if (! is_numeric($amount) || (float) $amount < 0 || (float) $amount > (float) PossessionDeclaredValue::MAX_DECLARED_VALUE) {
            throw ValidationException::withMessages(['amount' => __('Write an amount of zero or more.')]);
        }

        if ($this->sourceUrl() !== null && filter_var($this->sourceUrl(), FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages(['source_url' => __('Write a full web address, starting with https://.')]);
        }
    }

    private function create(): void
    {
        $this->valuation = PossessionValuation::query()->create([
            'account_id' => $this->possession->account_id,
            'vault_id' => $this->possession->vault_id,
            'possession_id' => $this->possession->id,
            'type' => $this->type,
            'valued_on' => $this->valuedOn->startOfDay(),
            'confidence' => $this->confidence,
            'amount' => number_format((float) mb_trim($this->amount), 2, '.', ''),
            'currency' => $this->currency,
            'valuer' => TextSanitizer::nullablePlainText($this->valuer),
            'method' => TextSanitizer::nullablePlainText($this->method),
            'reference_number' => TextSanitizer::nullablePlainText($this->referenceNumber),
            'source_url' => $this->sourceUrl(),
            'note' => TextSanitizer::nullablePlainText($this->note),
        ]);
    }

    private function sourceUrl(): ?string
    {
        return $this->sourceUrl === null || mb_trim($this->sourceUrl) === '' ? null : mb_trim($this->sourceUrl);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionValuationCreation,
            parameters: ['name' => $this->possession->name],
            loggable: $this->valuation,
        )->onQueue('low');
    }
}
