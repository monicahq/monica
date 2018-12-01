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



use App\Models\Contact\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountIdToActivities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_contact', function (Blueprint $table) {
            $table->integer('account_id')->after('contact_id');
        });

        $activitiesContacts = DB::table('activity_contact')->get();

        foreach ($activitiesContacts as $activityContact) {
            $contact = Contact::find($activityContact->contact_id);

            DB::table('activity_contact')
                ->where('activity_id', $activityContact->activity_id)
                ->where('contact_id', $activityContact->contact_id)
                ->update(['account_id' => $contact->account_id]);
        }
    }
}
