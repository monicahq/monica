<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Check what is said about a loan and turn it into the columns that record it. Recording and changing
 * a loan apply the same rules: the contact and the conditions are the possession's own, a due day and
 * a return day do not come before the start, a returned loan says when it came back and only a returned
 * one does, a deposit has a currency, and only one loan of a possession is active at a time.
 */
class PossessionLoanAttributes
{
    /**
     * @return array{direction: PossessionLoanDirectionEnum, status: PossessionLoanStatusEnum, started_on: CarbonImmutable, due_on: ?CarbonImmutable, returned_on: ?CarbonImmutable, condition_out_id: ?int, condition_back_id: ?int, deposit_amount: ?string, deposit_currency: ?CurrencyEnum, purpose: ?string, is_in_provenance: bool}
     */
    public static function from(
        User $user,
        PossessionLoanDirectionEnum $direction,
        PossessionLoanStatusEnum $status,
        CarbonImmutable $startedOn,
        ?CarbonImmutable $dueOn,
        ?CarbonImmutable $returnedOn,
        ?PossessionCondition $conditionOut,
        ?PossessionCondition $conditionBack,
        ?string $depositAmount,
        ?CurrencyEnum $depositCurrency,
        ?string $purpose,
        bool $isInProvenance,
    ): array {
        if ($conditionOut instanceof PossessionCondition && $conditionOut->account_id !== $user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }

        if ($conditionBack instanceof PossessionCondition && $conditionBack->account_id !== $user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }

        if ($dueOn !== null && $dueOn->toDateString() < $startedOn->toDateString()) {
            throw ValidationException::withMessages(['due_on' => __('The due date is before the loan started.')]);
        }

        $isReturned = $status === PossessionLoanStatusEnum::Returned;

        if ($isReturned && $returnedOn === null) {
            throw ValidationException::withMessages(['returned_on' => __('Say when it came back.')]);
        }

        if ($isReturned && $returnedOn->toDateString() < $startedOn->toDateString()) {
            throw ValidationException::withMessages(['returned_on' => __('It came back before it left.')]);
        }

        $amount = $depositAmount === null || mb_trim($depositAmount) === '' ? null : mb_trim($depositAmount);

        if ($amount !== null && (! is_numeric($amount) || (float) $amount < 0 || (float) $amount > (float) PossessionDeclaredValue::MAX_DECLARED_VALUE)) {
            throw ValidationException::withMessages(['deposit_amount' => __('Write an amount of zero or more.')]);
        }

        if ($amount !== null && $depositCurrency === null) {
            throw ValidationException::withMessages(['deposit_currency' => __('Choose the currency of the deposit.')]);
        }

        return [
            'direction' => $direction,
            'status' => $status,
            'started_on' => $startedOn->startOfDay(),
            'due_on' => $dueOn?->startOfDay(),
            'returned_on' => $isReturned ? $returnedOn->startOfDay() : null,
            'condition_out_id' => $conditionOut?->id,
            'condition_back_id' => $isReturned ? $conditionBack?->id : null,
            'deposit_amount' => $amount === null ? null : number_format((float) $amount, 2, '.', ''),
            'deposit_currency' => $amount === null ? null : $depositCurrency,
            'purpose' => TextSanitizer::nullablePlainText($purpose),
            'is_in_provenance' => $isInProvenance,
        ];
    }

    /**
     * A contact of another vault cannot hold a loan of this possession, even in the same account.
     */
    public static function ensureContactOfTheVault(Possession $possession, Contact $contact): void
    {
        if ($contact->vault_id !== $possession->vault_id) {
            throw new ModelNotFoundException('This contact is in another vault.');
        }
    }

    public static function refuseAnOpenLoanOfAPossessionNoLongerHeld(Possession $possession, PossessionLoanStatusEnum $status): void
    {
        if ($status === PossessionLoanStatusEnum::Returned) {
            return;
        }

        if (! $possession->status->isNoLongerHeld()) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => __('This possession is no longer in your hands, so only a returned loan can be recorded for it.'),
        ]);
    }

    /**
     * The possession row must be locked by the caller, so two loans sent at once cannot both find
     * none active.
     */
    public static function refuseASecondActiveLoan(Possession $possession, PossessionLoanStatusEnum $status, ?PossessionLoan $except = null): void
    {
        if ($status !== PossessionLoanStatusEnum::Active) {
            return;
        }

        $another = PossessionLoan::query()
            ->where('possession_id', $possession->id)
            ->where('status', PossessionLoanStatusEnum::Active->value)
            ->when($except instanceof PossessionLoan, fn ($query) => $query->whereKeyNot($except?->id))
            ->exists();

        if ($another) {
            throw ValidationException::withMessages([
                'status' => __('Another loan of this possession is active. Mark it as returned first.'),
            ]);
        }
    }
}
