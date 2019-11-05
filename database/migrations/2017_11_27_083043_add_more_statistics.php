<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statistics', function (Blueprint $table) {
            $table->integer('number_of_activities')->after('number_of_kids');
            $table->integer('number_of_addresses')->after('number_of_activities');
            $table->integer('number_of_api_calls')->after('number_of_addresses');
            $table->integer('number_of_calls')->after('number_of_api_calls');
            $table->integer('number_of_contact_fields')->after('number_of_calls');
            $table->integer('number_of_contact_field_types')->after('number_of_contact_fields');
            $table->integer('number_of_debts')->after('number_of_contact_field_types');
            $table->integer('number_of_entries')->after('number_of_debts');
            $table->integer('number_of_gifts')->after('number_of_entries');
            $table->integer('number_of_oauth_access_tokens')->after('number_of_notes');
            $table->integer('number_of_oauth_clients')->after('number_of_oauth_access_tokens');
            $table->integer('number_of_offsprings')->after('number_of_oauth_clients');
            $table->integer('number_of_progenitors')->after('number_of_offsprings');
            $table->integer('number_of_relationships')->after('number_of_progenitors');
            $table->integer('number_of_subscriptions')->after('number_of_relationships');
        });
    }
}
