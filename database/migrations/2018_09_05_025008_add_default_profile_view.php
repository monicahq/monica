<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultProfileView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_active_tab')->default('notes')->after('gifts_active_tab');
            $table->boolean('profile_new_life_event_badge_seen')->default(false)->after('profile_active_tab');
        });
    }
}
