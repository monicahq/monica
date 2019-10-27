<?php

namespace App\Helpers;

use Money\Money;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\App;
use Money\Currencies\ISOCurrencies;
use Illuminate\Support\Facades\Auth;
use Money\Currency as MoneyCurrency;
use Money\Formatter\IntlMoneyFormatter;

class MoneyHelper
{
    /**
     * Format a monetary amount with currency symbol.
     *
     * If the currency parameter is not passed, then the currency specified in
     * the users's settings will be used. If the currency setting is not
     * defined, then the amount will be returned without a currency symbol.
     *
     * @param  int|null     $amount   Amount to format.
     * @param  Currency     $currency Currency of amount.
     * @return string                 Amount formatted with currency symbol.
     */
    public static function format($amount, Currency $currency = null)
    {
        if (is_null($amount)) {
            $amount = 0;
        }

        if (! $currency && Auth::check()) {
            $currency = Auth::user()->currency;
        }

        if (! $currency) {
            $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::DECIMAL);

            return $numberFormatter->format($amount);
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $currencies = new ISOCurrencies();
        $minorUnitAdjustment = pow(10, $currencies->subunitFor($moneyCurrency));

        $money = new Money($amount * $minorUnitAdjustment, $moneyCurrency);

        $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $moneyFormatter->format($money);
    }
}
