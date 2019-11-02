<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePeopleToSignificantother extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('significant_others', function (Blueprint $table) {
            $table->dropColumn(
                'people_id'
            );
        });

        Schema::table('significant_others', function (Blueprint $table) {
            $table->integer('contact_id')->after('account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('significant_others', function (Blueprint $table) {
            $table->integer('people_id')->after('account_id');
        });

        Schema::table('significant_others', function (Blueprint $table) {
            $table->dropColumn(
                'contact_id'
            );
        });
    }
}
