<?php

namespace App\Helpers;

use Money\Money;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\App;
use Money\Currencies\ISOCurrencies;
use Illuminate\Support\Facades\Auth;
use Money\Currency as MoneyCurrency;
use Money\Parser\DecimalMoneyParser;
use Money\Formatter\IntlMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;

class MoneyHelper
{
    /**
     * Format a monetary amount with currency symbol.
     *
     * If the currency parameter is not passed, then the currency specified in
     * the users's settings will be used. If the currency setting is not
     * defined, then the amount will be returned without a currency symbol.
     *
     * @param  int|null $amount  Amount value in storable format (ex: 100 for 1,00€).
     * @param  Currency|int|null $currency Currency of amount.
     * @return string Formatted amount for display with currency symbol (ex '1,235.87 €').
     */
    public static function format($amount, $currency = null): string
    {
        if (is_null($amount)) {
            $amount = 0;
        }

        $currency = self::getCurrency($currency);

        if (! $currency) {
            $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::DECIMAL);

            return $numberFormatter->format($amount);
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $money = new Money($amount, $moneyCurrency);
        $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Format a monetary amount (without the currency).
     *
     * @param int|null $amount  Amount value in storable format (ex: 100 for 1,00€).
     * @param Currency|int|null $currency
     * @return string  Formatted amount for display without currency symbol (ex: '1234.50').
     */
    public static function getValue($amount, $currency = null): string
    {
        $currency = self::getCurrency($currency);

        if (! $currency) {
            return (string) ($amount / 100);
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $money = new Money($amount ?? 0, $moneyCurrency);
        $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::PATTERN_DECIMAL);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Format a monetary exchange value as storable integer.
     *
     * @param mixed|null $exchange  Amount value in exchange format (ex: 1.00).
     * @param Currency|int|null $currency
     * @return int  Amount as storable format (ex: 14500).
     */
    public static function formatInput($exchange, $currency): int
    {
        $currency = self::getCurrency($currency);

        if (! $currency) {
            return (int) ((float) $exchange * 100);
        }

        $moneyParser = new DecimalMoneyParser(new ISOCurrencies());
        $money = $moneyParser->parse((string) $exchange, $currency->iso);

        return (int) $money->getAmount();
    }

    /**
     * Format a monetary value as exchange value.
     *
     * @param int|null $amount  Amount value in storable format (ex: 100 for 1,00€).
     * @param Currency|int|null $currency
     * @return float  Real value of amount in exchange format (ex: 1.24).
     */
    public static function exchangeValue($amount, $currency): float
    {
        $currency = self::getCurrency($currency);

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $money = new Money($amount ?? 0, $moneyCurrency);
        $moneyFormatter = new DecimalMoneyFormatter(new ISOCurrencies());

        return (float) $moneyFormatter->format($money);
    }

    /**
     * Get currency object.
     *
     * @param Currency|int|null $currency
     * @return Currency|null
     */
    public static function getCurrency($currency): ?Currency
    {
        if (is_int($currency)) {
            $currency = Currency::find($currency);
        }

        if (! $currency && Auth::check()) {
            $currency = Auth::user()->currency;
        }

        return $currency;
    }
}
