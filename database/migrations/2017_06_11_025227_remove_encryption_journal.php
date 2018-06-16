<?php

use App\Models\Journal\Entry;
use Illuminate\Database\Migrations\Migration;

class RemoveEncryptionJournal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $entries = Entry::all();
        foreach ($entries as $entry) {
            echo $entry->id.' ';
            if (! is_null($entry->title)) {
                $entry->title = decrypt($entry->title);
            }

            if (! is_null($entry->post)) {
                $entry->post = decrypt($entry->post);
            }

            $entry->save();
        }
    }
}
