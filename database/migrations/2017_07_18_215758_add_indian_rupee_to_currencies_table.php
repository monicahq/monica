<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddIndianRupeeToCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('currencies')->insert(['iso' => 'INR', 'name' => 'Indian rupee', 'symbol'=>'₹']);
    }
}
