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

class AddInvitationsStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function ($table) {
            $table->integer('number_of_invitations_sent')->after('api_key')->nullable();
        });

        Schema::table('statistics', function ($table) {
            $table->integer('number_of_invitations_sent')->after('number_of_kids')->nullable();
            $table->integer('number_of_accounts_with_more_than_one_user')->after('number_of_invitations_sent')->nullable();
        });
    }
}
