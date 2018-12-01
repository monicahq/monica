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

class ChangeStringToBooleanForReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // create new column
        Schema::table('reminders', function ($table) {
            $table->boolean('is_a_birthday')->after('is_birthday');
        });

        $reminders = DB::table('reminders')->get();

        foreach ($reminders as $reminder) {
            if ($reminder->is_birthday == 'true') {
                DB::table('reminders')
                    ->where('id', $reminder->id)
                    ->update(['is_a_birthday' => 1]);
            } else {
                DB::table('reminders')
                    ->where('id', $reminder->id)
                    ->update(['is_a_birthday' => 0]);
            }
        }

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('is_birthday');
        });

        Schema::table('reminders', function ($table) {
            $table->renameColumn('is_a_birthday', 'is_birthday');
        });
    }
}
