<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenamePicnicked extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('default_activity_types')
            ->where('translation_key', 'picknicked')
            ->update(['translation_key' => 'picnicked']);
        DB::table('activity_types')
            ->where('translation_key', 'picknicked')
            ->update(['translation_key' => 'picnicked']);
    }
}
