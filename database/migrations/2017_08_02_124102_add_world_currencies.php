<?php
/**
 * Add world currencies to the db
 * Dataset taken from.
 * @see https://github.com/wiredmax/world-currencies
 */
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddWorldCurrencies extends Migration
{
    /**
     * List of iso codes of currencies to ignore
     * They already exist in the db.
     *
     * @var string[]
     */
    private $ignore = ['CAD', 'USD', 'GBP', 'EUR', 'RUB', 'ZAR'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $currencies = json_decode(file_get_contents(__DIR__.'/2017_08_02_124102_add_world_currencies.json'), true);

        DB::transaction(function () use ($currencies) {
            foreach ($currencies as $currency) {
                if (in_array($currency['iso']['code'], $this->ignore)) {
                    continue;
                }

                DB::table('currencies')->insert([
                    'iso' => $currency['iso']['code'],
                    'name' => $currency['name'],
                    'symbol' => $currency['units']['major']['symbol'],
                ]);
            }
        });
    }
}
