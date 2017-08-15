<?php

use App\Gift;
use App\Note;
use App\Task;
use App\Contact;
use App\Activity;
use App\Reminder;
use Illuminate\Database\Migrations\Migration;

class CalculateStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Contact::unsetEventDispatcher();
        foreach (Contact::all() as $contact) {
            $contact->number_of_reminders = Reminder::where('contact_id', $contact->id)->count();
            $contact->number_of_notes = Note::where('contact_id', $contact->id)->count();
            $contact->number_of_activities = Activity::where('contact_id', $contact->id)->count();
            $contact->number_of_gifts_ideas = Gift::where('contact_id', $contact->id)->where('is_an_idea', 'true')->count();
            $contact->number_of_gifts_offered = Gift::where('contact_id', $contact->id)->where('has_been_offered', 'true')->count();
            $contact->number_of_tasks_in_progress = Task::where('contact_id', $contact->id)->where('status', 'inprogress')->count();
            $contact->number_of_tasks_completed = Task::where('contact_id', $contact->id)->where('status', 'completed')->count();
            $contact->save();
        }
    }
}
