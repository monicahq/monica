<?php

use function Safe\json_decode;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class CurrenciesTableSeed extends Migration
{
    private const CURRENCIES_JSON_FILE = __DIR__.'/../seeds/json/2017_08_02_124102_add_world_currencies.json';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $currencies = collect(json_decode(file_get_contents(self::CURRENCIES_JSON_FILE), true));

        $currentCurrencies = Currency::all()
            ->map(function ($currency) {
                return $currency->iso;
            })
            ->toArray();

        $insert = $currencies->reject(function ($currency) use ($currentCurrencies) {
            return in_array($currency['iso']['code'], $currentCurrencies);
        })->map(function ($currency) {
            return [
                'iso' => $currency['iso']['code'],
                'name' => $currency['name'],
                'symbol' => $currency['units']['major']['symbol'],
            ];
        })->values()->toArray();

        DB::table('currencies')->insert($insert);
    }
}
