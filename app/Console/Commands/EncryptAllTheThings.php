<?php

namespace App\Console\Commands;

use DB;
use App\Kid;
use App\Note;
use App\Task;
use App\Contact;
use App\Reminder;
use App\SignificantOther;
use Illuminate\Console\Command;

class EncryptAllTheThings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:encryptallthethings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ONE TIME USE ONLY - Encrypt all the data in the database';

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
        //
        // CONTACTS
        //
        $contacts = Contact::all();
        foreach ($contacts as $contact) {
            // if (!is_null($contact->first_name)) {
            //     $contact->first_name = encrypt($contact->first_name);
            // }

            // if (!is_null($contact->middle_name)) {
            //     $contact->middle_name = encrypt($contact->middle_name);
            // }

            // if (!is_null($contact->last_name)) {
            //     $contact->last_name = encrypt($contact->last_name);
            // }

            // if (!is_null($contact->street)) {
            //     $contact->street = encrypt($contact->street);
            // }

            // if (!is_null($contact->city)) {
            //     $contact->city = encrypt($contact->city);
            // }

            // if (!is_null($contact->province)) {
            //     $contact->province = encrypt($contact->province);
            // }

            // if (!is_null($contact->postal_code)) {
            //     $contact->postal_code = encrypt($contact->postal_code);
            // }

            // if (!is_null($contact->food_preferencies)) {
            //     $contact->food_preferencies = encrypt($contact->food_preferencies);
            // }

            if (! is_null($contact->email)) {
                $contact->email = encrypt($contact->email);
            }

            $contact->save();
        }

        //
        // KIDS
        //
        // $kids = Kid::all();
        // foreach ($kids as $kid) {
        //     if (!is_null($kid->first_name)) {
        //         $kid->first_name = encrypt($kid->first_name);
        //     }

        //     $kid->save();
        // }

        //
        // NOTES
        //
        // $notes = Note::all();
        // foreach ($notes as $note) {
        //     if (!is_null($note->title)) {
        //         $note->title = encrypt($note->title);
        //     }

        //     if (!is_null($note->body)) {
        //         $note->body = encrypt($note->body);
        //     }

        //     $note->save();
        // }

        //
        // PEOPLES
        //
        // $peoples = People::all();
        // foreach ($peoples as $people) {
        //     if (!is_null($people->sortable_name)) {
        //         $people->sortable_name = encrypt($people->sortable_name);
        //     }

        //     $people->save();
        // }

        //
        // REMINDERS
        //
        // $reminders = Reminder::all();
        // foreach ($reminders as $reminder) {
        //     if (!is_null($reminder->title)) {
        //         $reminder->title = encrypt($reminder->title);
        //     }

        //     if (!is_null($reminder->description)) {
        //         $reminder->description = encrypt($reminder->description);
        //     }

        //     $reminder->save();
        // }

        //
        // SIGNIFICANT OTHER
        //
        // $significantOthers = SignificantOther::all();
        // foreach ($significantOthers as $significantOther) {
        //     if (!is_null($significantOther->first_name)) {
        //         $significantOther->first_name = encrypt($significantOther->first_name);
        //     }

        //     $significantOther->save();
        // }

        //
        // TASKS
        //
        // $tasks = Task::all();
        // foreach ($tasks as $task) {
        //     if (!is_null($task->title)) {
        //         $task->title = encrypt($task->title);
        //     }

        //     if (!is_null($task->description)) {
        //         $task->description = encrypt($task->description);
        //     }

        //     $task->save();
        // }
    }
}
