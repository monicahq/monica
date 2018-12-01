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



use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePredefinedReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $reminders = Reminder::all();
        foreach ($reminders as $reminder) {
            echo $reminder->id.' ';
            if (! is_null($reminder->title)) {
                $reminder->title = decrypt($reminder->title);
            }

            if (! is_null($reminder->description)) {
                $reminder->description = decrypt($reminder->description);
            }

            $reminder->save();
        }

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(
                'reminder_type_id'
            );
        });

        Schema::drop('reminder_types');
    }
}
