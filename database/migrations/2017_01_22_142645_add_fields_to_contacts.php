<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('has_kids')->default('false')->after('gender');
            $table->integer('number_of_kids')->default('0')->after('has_kids');
            $table->date('last_talked_to')->nullable()->after('number_of_kids');
            $table->integer('number_of_reminders')->default('0')->after('last_talked_to');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'entity_id', 'people_id', 'twitter_id', 'instagram_id'
            );
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->integer('contact_id')->after('account_id');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(
                'people_id', 'user_id_of_the_writer'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'has_kids', 'number_of_kids', 'last_talked_to', 'number_of_reminders'
            );
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(
                'contact_id'
            );
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('entity_id')->nullable();
            $table->integer('people_id')->nullable();
            $table->string('twitter_id')->nullable();
            $table->string('instagram_id')->nullable();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->integer('people_id')->after('account_id');
            $table->integer('user_id_of_the_writer');
        });
    }
}
