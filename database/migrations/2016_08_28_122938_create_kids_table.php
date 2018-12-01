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
use Illuminate\Database\Migrations\Migration;

class CreateKidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // drop existing kids table (created in a former migration)
        Schema::drop('kids');

        Schema::create('kids', function ($table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('child_of_people_id');
            $table->enum('gender', ['male', 'female']);
            $table->string('first_name');
            $table->string('is_birthdate_approximate')->default('false')->nullable();
            $table->dateTime('birthdate')->nullable();
            $table->longText('food_preferencies')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('peoples', function ($table) {
            $table->string('has_kids')->default('false')->after('object_id')->nullable();
            $table->integer('number_of_kids')->after('has_kids')->nullable();
        });

        Schema::table('contacts', function ($table) {
            $table->dropColumn(['first_parent_id', 'status', 'has_kids', 'warned_about_birthdate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('kids');

        Schema::table('peoples', function ($table) {
            $table->dropColumn('has_kids');
        });
    }
}
