<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class RenamePreferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function ($table) {
            $table->renameColumn('food_preferencies', 'food_preferences');
        });

        DB::table('default_contact_modules')
            ->where('translation_key', 'people.food_preferencies_title')
            ->update(['translation_key' => 'people.food_preferences_title']);
        DB::table('modules')
            ->where('translation_key', 'people.food_preferencies_title')
            ->update(['translation_key' => 'people.food_preferences_title']);
    }
}
