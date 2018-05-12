<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateBadTranslationKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('activity_types')
            ->where('id', 1)
            ->update(['key' => 'just_hung_out']);
    }
}
