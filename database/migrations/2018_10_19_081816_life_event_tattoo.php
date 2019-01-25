<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class LifeEventTattoo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('default_life_event_types')
            ->where('translation_key', 'tatoo_or_piercing')
            ->update([
                'translation_key' => 'tattoo_or_piercing',
            ]);

        DB::table('life_event_types')
            ->where('default_life_event_type_key', 'tatoo_or_piercing')
            ->update([
                'default_life_event_type_key' => 'tattoo_or_piercing',
                'name' => trans('settings.personalization_life_event_type_tattoo_or_piercinge', [], 'en'),
            ]);
    }
}
