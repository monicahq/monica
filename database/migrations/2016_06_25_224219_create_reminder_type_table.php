<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReminderTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminder_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description');
            $table->string('translation_key');
            $table->timestamps();
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->integer('reminder_type_id')->after('people_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reminder_types');

        Schema::table('reminders', function ($table) {
            $table->dropColumn(['reminder_type_id']);
        });
    }
}
