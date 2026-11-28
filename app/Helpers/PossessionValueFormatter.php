<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\NumberFormatEnum;
use App\Models\Possession;
use App\Models\User;
use NumberFormatter;

/**
 * The declared value of a possession as somebody reads it: the language places the currency symbol,
 * the person's number format picks the separators. Amounts in different currencies are never added.
 */
class PossessionValueFormatter
{
    public function __construct(private readonly User $user) {}

    public function format(?string $amount, ?string $currency): string
    {
        if ($amount === null || $currency === null) {
            return '—';
        }

        return $this->currencyFormatter()->formatCurrency((float) $amount, mb_strtoupper($currency)) ?: '—';
    }

    /**
     * An amount that may carry cents, such as a price paid: written whole when it is a round number,
     * with its cents otherwise.
     */
    public function formatExact(?string $amount, ?string $currency): string
    {
        if ($amount === null || $currency === null) {
            return '—';
        }

        $digits = str_ends_with($amount, '.00') ? 0 : 2;

        return $this->currencyFormatter($digits)->formatCurrency((float) $amount, mb_strtoupper($currency)) ?: '—';
    }

    /** @param  iterable<Possession>  $possessions */
    public function total(iterable $possessions): string
    {
        $sums = [];

        foreach ($possessions as $possession) {
            if ($possession->declared_value === null || $possession->declared_value_currency === null) {
                continue;
            }

            $currency = $possession->declared_value_currency;
            $sums[$currency] = ($sums[$currency] ?? 0) + (float) $possession->declared_value;
        }

        if ($sums === []) {
            return '—';
        }

        arsort($sums);

        return collect($sums)
            ->map(fn (float $sum, string $currency): string => $this->format((string) $sum, $currency))
            ->implode(__(', '));
    }

    private function currencyFormatter(int $digits = 0): NumberFormatter
    {
        $numberFormat = $this->user->number_format
            ?? NumberFormatEnum::tryFrom((string) AvailableLocales::details(app()->getLocale())['number_format'])
            ?? NumberFormatEnum::CommaAndDot;

        // A locale whose digits differ from the ones written everywhere else in
        // Monica names the numbering system to use, such as `latn` for Arabic.
        $numbers = AvailableLocales::details(app()->getLocale())['numbers'] ?? null;
        $locale = app()->getLocale().(is_string($numbers) ? '@numbers='.$numbers : '');

        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $digits);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $digits);
        $formatter->setSymbol(NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, $numberFormat->thousandsSeparator());
        $formatter->setSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $numberFormat->decimalSeparator());

        return $formatter;
    }
}
