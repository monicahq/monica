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



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewAvatarToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('has_avatar_bool')->default(0);
        });

        DB::table('contacts')
            ->where('has_avatar', 'true')
            ->update(['has_avatar_bool' => 1]);

        // dropping the non boolean column
        // cant rename the column because there is an enum field in this table
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('has_avatar');
        });

        // change the column to boolean
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('has_avatar')->default(0)->after('food_preferencies');
        });

        DB::table('contacts')
            ->where('has_avatar_bool', 1)
            ->update(['has_avatar' => 1]);

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('avatar_external_url', 400)->nullable()->after('has_avatar');
        });
    }
}
