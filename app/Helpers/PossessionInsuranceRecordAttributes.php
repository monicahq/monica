<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Check what is said about an insurance record and turn it into the columns that record it. Recording
 * and changing a record apply the same rules: an insurer is named, the insured value and the excess are
 * zero or more, the period does not end before it starts, an email is an email, and text loses its markup.
 */
class PossessionInsuranceRecordAttributes
{
    /**
     * @return array{insurer: string, status: PossessionInsuranceStatusEnum, policy_number: ?string, coverage: ?string, insured_value: string, excess: ?string, currency: CurrencyEnum, starts_on: ?CarbonImmutable, ends_on: ?CarbonImmutable, is_listed_individually: bool, contact_name: ?string, contact_email: ?string, contact_phone: ?string, note: ?string}
     */
    public static function from(
        string $insurer,
        PossessionInsuranceStatusEnum $status,
        string $insuredValue,
        CurrencyEnum $currency,
        ?string $policyNumber,
        ?string $coverage,
        ?string $excess,
        ?CarbonImmutable $startsOn,
        ?CarbonImmutable $endsOn,
        bool $isListedIndividually,
        ?string $contactName,
        ?string $contactEmail,
        ?string $contactPhone,
        ?string $note,
    ): array {
        $insurer = TextSanitizer::plainText($insurer);

        if ($insurer === '') {
            throw ValidationException::withMessages(['insurer' => __('Name the insurer.')]);
        }

        $insuredValue = self::amount('insured_value', $insuredValue);

        if ($insuredValue === null) {
            throw ValidationException::withMessages(['insured_value' => __('Write an amount of zero or more.')]);
        }

        if ($startsOn !== null && $endsOn !== null && $endsOn->lt($startsOn)) {
            throw ValidationException::withMessages(['ends_on' => __('The end cannot come before the start.')]);
        }

        $contactEmail = $contactEmail === null || mb_trim($contactEmail) === '' ? null : mb_trim($contactEmail);

        if ($contactEmail !== null && filter_var($contactEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['contact_email' => __('Write a full email address.')]);
        }

        return [
            'insurer' => $insurer,
            'status' => $status,
            'policy_number' => TextSanitizer::nullablePlainText($policyNumber),
            'coverage' => TextSanitizer::nullablePlainText($coverage),
            'insured_value' => $insuredValue,
            'excess' => self::amount('excess', $excess),
            'currency' => $currency,
            'starts_on' => $startsOn?->startOfDay(),
            'ends_on' => $endsOn?->startOfDay(),
            'is_listed_individually' => $isListedIndividually,
            'contact_name' => TextSanitizer::nullablePlainText($contactName),
            'contact_email' => $contactEmail,
            'contact_phone' => TextSanitizer::nullablePlainText($contactPhone),
            'note' => TextSanitizer::nullablePlainText($note),
        ];
    }

    private static function amount(string $key, ?string $amount): ?string
    {
        $amount = $amount === null || mb_trim($amount) === '' ? null : mb_trim($amount);

        if ($amount === null) {
            return null;
        }

        if (! is_numeric($amount) || (float) $amount < 0 || (float) $amount > (float) PossessionDeclaredValue::MAX_DECLARED_VALUE) {
            throw ValidationException::withMessages([$key => __('Write an amount of zero or more.')]);
        }

        return number_format((float) $amount, 2, '.', '');
    }
}
