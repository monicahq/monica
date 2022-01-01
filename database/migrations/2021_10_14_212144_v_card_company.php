<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class VCardCompany extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('contacts')
            ->where('company', ';')
            ->update(['company' => null]);
    }
}
