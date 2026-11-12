<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\CurrencyEnum;
use Illuminate\Validation\ValidationException;

/**
 * Check what a possession would cost to replace and turn it into the columns that record it. Creating
 * and changing a possession apply the same rules: the value is zero or more and carries its currency.
 */
class PossessionDeclaredValue
{
    public const string MAX_DECLARED_VALUE = '9999999999.99';

    /** @return array{declared_value: ?string, declared_value_currency: ?string} */
    public static function from(?string $declaredValue, ?CurrencyEnum $declaredValueCurrency): array
    {
        $declaredValue = $declaredValue === null || mb_trim($declaredValue) === '' ? null : mb_trim($declaredValue);

        if ($declaredValue !== null && (! is_numeric($declaredValue) || (float) $declaredValue < 0 || (float) $declaredValue > (float) self::MAX_DECLARED_VALUE)) {
            throw ValidationException::withMessages(['declared_value' => __('Write a value of zero or more.')]);
        }

        if ($declaredValue !== null && $declaredValueCurrency === null) {
            throw ValidationException::withMessages(['declared_value_currency' => __('Choose the currency of this value.')]);
        }

        return [
            'declared_value' => $declaredValue === null ? null : number_format((float) $declaredValue, 2, '.', ''),
            'declared_value_currency' => $declaredValue === null ? null : $declaredValueCurrency?->value,
        ];
    }
}
