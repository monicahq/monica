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

class RemovePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('peoples');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('peoples', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_id');
            $table->integer('account_id');
            $table->enum('type', ['entity', 'contact']);
            $table->integer('object_id');
            $table->string('sortable_name')->nullable()->after('object_id');
            $table->string('has_kids')->default('false')->after('object_id')->nullable();
            $table->integer('number_of_kids')->after('has_kids')->nullable();
            $table->dateTime('last_talked_to')->nullable();
            $table->dateTime('viewed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
