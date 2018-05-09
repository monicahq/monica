<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddDanishKronerToCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('currencies')->insert(['iso' => 'DKK', 'name' => 'Danish krone', 'symbol'=>'kr.']);
    }
}
