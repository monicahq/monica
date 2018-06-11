<?php

use Illuminate\Support\Facades\DB;
use App\Models\Relationship\Relationship;
use Illuminate\Database\Migrations\Migration;

class FixContactsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // because of older migrations, we have some contacts in the relationships table with an ID, but
        // the actual object doesn't exist. we need to find those contacts and delete the entries in the
        // relationships table.
        $relationships = Relationship::select('id', 'contact_is', 'of_contact')->get();
        $lineContactIsToDelete = collect([]);
        $lineOfContactToDelete = collect([]);

        foreach ($relationships as $relationship) {
            $contact = DB::table('contacts')->where('id', $relationship->contact_is)->first();
            if (! $contact) {
                $lineContactIsToDelete->push($relationship);
            }

            $contact = DB::table('contacts')->where('id', $relationship->of_contact)->first();
            if (! $contact) {
                $lineOfContactToDelete->push($relationship);
            }
        }

        foreach ($lineContactIsToDelete as $relationship) {
            DB::table('relationships')->where('id', $relationship->id)->delete();
        }

        foreach ($lineOfContactToDelete as $relationship) {
            DB::table('relationships')->where('id', $relationship->id)->delete();
        }
    }
}
