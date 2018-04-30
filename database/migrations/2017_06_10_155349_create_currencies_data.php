<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrenciesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('iso');
            $table->string('name');
            $table->string('symbol');
        });

        //defaults
        DB::table('currencies')->insert(['iso' => 'CAD', 'name' => 'Canadian Dollar', 'symbol'=>'$']);
        DB::table('currencies')->insert(['iso' => 'USD', 'name' => 'US Dollar', 'symbol'=>'$']);
        DB::table('currencies')->insert(['iso' => 'GBP', 'name' => 'British Pound', 'symbol'=>'£']);
        DB::table('currencies')->insert(['iso' => 'EUR', 'name' => 'Euro', 'symbol'=>'€']);
        DB::table('currencies')->insert(['iso' => 'RUB', 'name' => 'Russian Ruble', 'symbol'=>'₽']);

        Schema::table('users', function (Blueprint $table) {
            $dollarResult = DB::table('currencies')->select('id')->where('iso', '=', 'USD')->value('id');
            $table->integer('currency_id')->after('timezone')->default(
            $dollarResult
          );
        });
    }
}
