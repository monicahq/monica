<?php

namespace App\Domains\Settings\ManageCurrencies\Web\ViewHelpers;

use App\Models\Account;
use Illuminate\Support\Collection;

class CurrencyIndexViewHelper
{
    public static function data(Account $account, ?int $currencyId = null): Collection
    {
        $currenciesCollection = $account->currencies()
            ->orderBy('code')
            ->where('active', true)
            ->get()
            ->map(function ($currency) use ($currencyId) {
                return [
                    'id' => $currency->id,
                    'name' => $currency->code,
                    'selected' => $currencyId ? $currencyId === $currency->id : null,
                ];
            });

        return $currenciesCollection;
    }
}
