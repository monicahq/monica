<?php

use Illuminate\Database\Migrations\Migration;

class ChangeLocaleInDb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')
            ->where('locale', 'pt-br')
            ->update(['locale' => 'pt']);

        DB::table('users')
            ->where('locale', 'cz')
            ->update(['locale' => 'cs']);
    }
}
