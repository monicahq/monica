<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
