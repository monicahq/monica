<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddBrazilianRealToCurrencies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('currencies')->insert(['iso' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$']);
    }
}
