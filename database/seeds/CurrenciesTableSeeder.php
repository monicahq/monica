<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/




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
