<?php

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
