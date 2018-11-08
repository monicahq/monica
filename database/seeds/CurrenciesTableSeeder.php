<?php


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed world currencies to the database.
 *
 * Originally contributed by Themodem (https://github.com/Themodem)
 * in PR#509 (https://github.com/monicahq/monica/pull/509)
 */
class CurrenciesTableSeeder extends Seeder
{
    private const CURRENCIES_JSON_FILE = __DIR__.'/json/2017_08_02_124102_add_world_currencies.json';

    /**
     * List of iso codes of currencies to ignore
     * They already exist in the db.
     *
     * @var string[]
     */
    private $ignore = ['CAD', 'USD', 'GBP', 'EUR', 'RUB', 'ZAR'];

    /**
     * Runs the seeder.
     *
     * @return void
     */
    public function run()
    {
        $currencies = collect(json_decode(file_get_contents(self::CURRENCIES_JSON_FILE), $assoc = true));

        $insert = $currencies->reject(function ($currency) {
            return in_array($currency['iso']['code'], $this->ignore);
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
