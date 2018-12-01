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

class ChangeNumberTasksContact extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'number_of_tasks'
            );
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('number_of_tasks_in_progress')->after('number_of_gifts_offered');
            $table->integer('number_of_tasks_completed')->after('number_of_tasks_in_progress');
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
                'number_of_tasks_completed', 'number_of_gifts_offered'
            );
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('number_of_tasks');
        });
    }
}
