<?php

namespace App\Console\Commands;

use DB;
use App\Kid;
use App\Note;
use App\Contact;
use App\Activity;
use App\SignificantOther;
use Illuminate\Console\Command;

class RemoveEncryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:removeencryption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One time use only. Remove all encrypted fields and change them to non encrypted.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $significantothers = SignificantOther::all();
        foreach ($significantothers as $significantother) {
            echo $significantother->id;
            $significantother->first_name = decrypt($significantother->first_name);

            if (! is_null($significantother->last_name) and trim($significantother->last_name) != '') {
                $significantother->last_name = decrypt($significantother->last_name);
            }

            $significantother->save();
        }

        $activities = Activity::all();
        foreach ($activities as $activity) {
            echo $activity->id;
            if (! is_null($activity->description)) {
                $activity->description = decrypt($activity->description);
                $activity->save();
            }
        }

        $kids = Kid::all();
        foreach ($kids as $kid) {
            echo $kid->id;
            $kid->first_name = decrypt($kid->first_name);
            $kid->save();
        }

        $notes = Note::all();
        foreach ($notes as $note) {
            echo $note->id;
            $note->body = decrypt($note->body);
            $note->save();
        }

        $contacts = Contact::all();
        foreach ($contacts as $contact) {
            if (! is_null($contact->food_preferencies) and trim($contact->food_preferencies) != '') {
                $contact->food_preferencies = decrypt($contact->food_preferencies);
                $contact->save();
            }
        }
    }
}
