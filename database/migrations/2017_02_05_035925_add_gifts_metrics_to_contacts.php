<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGiftsMetricsToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('number_of_gifts_ideas')->default(0)->after('number_of_activities');
            $table->integer('number_of_gifts_received')->default(0)->after('number_of_gifts_ideas');
            $table->integer('number_of_gifts_offered')->default(0)->after('number_of_gifts_received');
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
                'number_of_gifts_ideas', 'number_of_gifts_received', 'number_of_gifts_offered'
            );
        });
    }
}
