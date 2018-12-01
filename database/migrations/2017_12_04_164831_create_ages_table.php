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

class CreateAgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_dates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->boolean('is_age_based')->default(0);
            $table->boolean('is_year_unknown')->default(0);
            $table->date('date');
            $table->integer('reminder_id')->nullable();
            $table->timestamps();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('birthday_special_date_id')->nullable()->after('last_talked_to');
            $table->integer('deceased_special_date_id')->nullable()->after('is_dead');
            $table->integer('first_met_special_date_id')->nullable()->after('first_met_through_contact_id');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->integer('special_date_id')->nullable()->after('contact_id');
        });
    }
}
