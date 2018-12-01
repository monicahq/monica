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

class RefactorUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_user_id',
                'access_token',
                'amazon_store_country_id',
                'onboarding_journal_dismissed',
                'send_sms_alert',
                'phone_number',
                'gender',
                'deleted_at',
            ]);

            $table->integer('invited_by_user_id')->after('contacts_sort_order')->nullable();
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('invited_by_user_id');
            $table->string('email');
            $table->string('invitation_key');
            $table->timestamps();
        });
    }
}
