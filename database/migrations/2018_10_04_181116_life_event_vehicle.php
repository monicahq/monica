<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class LifeEventVehicle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('default_life_event_types')
            ->where('translation_key', 'new_vehicule')
            ->update([
                'translation_key' => 'new_vehicle',
            ]);

        DB::table('life_event_types')
            ->where('default_life_event_type_key', 'new_vehicule')
            ->update([
                'default_life_event_type_key' => 'new_vehicle',
                'name' => trans('settings.personalization_life_event_type_new_vehicle', [], 'en'),
            ]);
    }
}
