<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReminderIdToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('birthday_reminder_id')->nullable()->after('birthdate');
        });

        Schema::table('kids', function (Blueprint $table) {
            $table->integer('birthday_reminder_id')->nullable()->after('birthdate');
        });

        Schema::table('significant_others', function (Blueprint $table) {
            $table->integer('birthday_reminder_id')->nullable()->after('birthdate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function ($table) {
            $table->dropColumn('fluid_container');
        });

        Schema::table('kids', function ($table) {
            $table->dropColumn('fluid_container');
        });

        Schema::table('significant_others', function ($table) {
            $table->dropColumn('fluid_container');
        });
    }
}
