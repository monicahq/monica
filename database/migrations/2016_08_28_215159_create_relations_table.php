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

class CreateRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function ($table) {
            $table->dropColumn(['second_parent_id', 'couple_status']);
        });

        Schema::create('significant_others', function ($table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('people_id');
            $table->enum('status', ['active', 'past']);
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('is_birthdate_approximate')->default('false')->nullable();
            $table->dateTime('birthdate')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('significant_others');
    }
}
