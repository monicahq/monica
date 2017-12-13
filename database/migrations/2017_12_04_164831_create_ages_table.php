<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_dates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->boolean('is_age_based')->default(0);
            $table->boolean('is_year_unknown')->default(0);
            $table->date('date');
            $table->integer('reminder_id')->nullable();
            $table->timestamps();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('birthday_special_date_id')->nullable()->after('last_talked_to');
            $table->integer('deceased_special_date_id')->nullable()->after('is_dead');
            $table->integer('first_met_special_date_id')->nullable()->after('first_met_through_contact_id');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->integer('special_date_id')->nullable()->after('contact_id');
        });
    }
}
