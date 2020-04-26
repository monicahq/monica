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
     * Amount must be an integer, in exchange format.
     * i.e. '100' for 1,00€
     *
     * If the currency parameter is not passed, then the currency specified in
     * the users's settings will be used. If the currency setting is not
     * defined, then the amount will be returned without a currency symbol.
     *
     * @param  int|null $amount   Amount to format.
     * @param  Currency|int|null $currency Currency of amount.
     * @return string Amount formatted with currency symbol.
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
     * Amount must be an integer, in exchange format.
     * i.e. '100' for 1,00€
     *
     * @param int|null $amount
     * @param Currency|int|null $currency
     * @return string
     */
    public static function formatValue($amount, $currency = null): string
    {
        $currency = self::getCurrency($currency);

        if (!$currency) {
            return (string) ($amount / 100);
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $money = new Money($amount ?? 0, $moneyCurrency);
        $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::PATTERN_DECIMAL);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Format a monetary amount in exchange value with currency symbol.
     *
     * @param float|null $exchange
     * @param Currency|int|null $currency
     * @return string
     */
    public static function display($exchange, $currency = null): string
    {
        $currency = self::getCurrency($currency);

        return self::format(self::formatInput($exchange, $currency), $currency);
    }

    /**
     * Format a monetary exchange value as storable integer.
     *
     * @param mixed|null $exchange
     * @param Currency|int|null $currency
     * @return int
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
     * @param int|null $amount
     * @param Currency|int|null $currency
     * @return float
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
     * Get unit adjustement value for the currency.
     *
     * @param Currency|int|null $currency
     * @return int
     */
    public static function unitAdjustment($currency): int
    {
        $currency = self::getCurrency($currency);

        if (!$currency) {
            return 100;
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $currencies = new ISOCurrencies();

        return (int) pow(10, $currencies->subunitFor($moneyCurrency));
    }

    /**
     * Get currency object.
     *
     * @param Currency|int|null $currency
     * @return Currency|null
     */
    private static function getCurrency($currency): ?Currency
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
