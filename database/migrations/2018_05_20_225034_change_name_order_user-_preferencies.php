<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ChangeNameOrderUserPreferencies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')
            ->where('name_order', 'firstname_first')
            ->update(['name_order' => 'firstname_lastname_nickname']);

        DB::table('users')
            ->where('name_order', 'lastname_first')
            ->update(['name_order' => 'lastname_firstname_nickname']);
    }
}
