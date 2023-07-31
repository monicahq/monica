<?php

namespace App\Domains\Settings\ManageCurrencies\Web\ViewHelpers;

use App\Models\Account;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class PersonalizeCurrencyIndexViewHelper
{
    public static function data(Account $account): array
    {
        $currencies = $account->currencies()
            ->orderBy('code', 'asc')
            ->get();

        $collection = collect();
        foreach ($currencies as $currency) {
            $collection->push(self::dtoCurrency($currency, $account));
        }

        return [
            'currencies' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'enable_all' => route('settings.personalize.currency.store'),
                'disable_all' => route('settings.personalize.currency.destroy'),
            ],
        ];
    }

    public static function dtoCurrency(Currency $currency, Account $account): array
    {
        $record = DB::table('account_currency')
            ->where([
                'account_id' => $account->id,
                'currency_id' => $currency->id,
            ])
            ->first();

        return [
            'id' => $currency->id,
            'code' => $currency->code,
            'name' => __('currencies.'.$currency->code),
            'active' => $record->active,
            'url' => [
                'update' => route('settings.personalize.currency.update', [
                    'currency' => $currency->id,
                ]),
            ],
        ];
    }
}
