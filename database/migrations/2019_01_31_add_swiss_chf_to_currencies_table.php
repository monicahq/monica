<?php

use Illuminate\Database\Migrations\Migration;

class AddSwissCHFToCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('currencies')->insert(['iso' => 'CHF', 'name' => 'Swiss CHF', 'symbol' => 'CHF']);
    }
}
