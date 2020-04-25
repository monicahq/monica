<?php

namespace App\Helpers;

use Money\Money;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\App;
use Money\Currencies\ISOCurrencies;
use Illuminate\Support\Facades\Auth;
use Money\Currency as MoneyCurrency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlMoneyFormatter;

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

        if (is_int($currency)) {
            $currency = Currency::find($currency);
        }

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
     * Format a monetary amount as exchange value.
     *
     * Amount must be an integer, in exchange format.
     * i.e. '100' for 1,00€
     *
     * @param int|null $amount
     * @param Currency|int|null $currency
     * @return string
     */
    public static function exchangeValue($amount, $currency = null): string
    {
        if (is_int($currency)) {
            $currency = Currency::find($currency);
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);

        $money = new Money($amount ?? 0, $moneyCurrency);

        $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::DECIMAL);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Format a monetary amount in exchange value with currency symbol.
     *
     * @param double|null $exchange
     * @param Currency|int|null $currency
     * @return string
     */
    public static function display($exchange, $currency = null): string
    {
        return self::format(self::formatInput($exchange, $currency), $currency);
    }

    /**
     * Format a monetary exchange value as storable integer.
     *
     * @param double|null $exchange
     * @param Currency|int $currency
     * @return int
     */
    public static function formatInput($exchange, $currency): int
    {
        $minorUnitAdjustment = self::unitAdjustment($currency);

        return (int) (($exchange ?? 0) * $minorUnitAdjustment);
    }

    /**
     * Get unit adjustement value for the currency.
     *
     * @param Currency|int $currency
     * @return int
     */
    public static function unitAdjustment($currency): int
    {
        if (is_int($currency)) {
            $currency = Currency::find($currency);
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $currencies = new ISOCurrencies();

        return pow(10, $currencies->subunitFor($moneyCurrency));
    }
}
