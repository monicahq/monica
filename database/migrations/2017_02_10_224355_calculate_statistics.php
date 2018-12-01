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



use App\Models\Contact\Gift;
use App\Models\Contact\Note;
use App\Models\Contact\Task;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use App\Models\Contact\Reminder;
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
