<?php

namespace App\Helpers;

use App\Models\Settings\Currency;

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
     * @param  App\Currency $currency Currency of amount.
     * @return string                 Amount formatted with currency symbol.
     */
    public static function format($amount, Currency $currency = null)
    {
        if (is_null($amount)) {
            $amount = 0;
        }

        if (! $currency && auth()->user()) {
            $currency = auth()->user()->currency;
        }

        if ($currency) {
            switch ($currency->iso) {
                case 'BRL':
                    $amount = number_format($amount, 2, ',', '.');
                    break;
            }

            $amount = $currency->symbol.$amount;
        }

        return (string) $amount;
    }
}
