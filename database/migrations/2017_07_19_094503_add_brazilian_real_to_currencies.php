<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
