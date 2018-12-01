<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

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
