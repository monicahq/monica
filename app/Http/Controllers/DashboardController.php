<?php

namespace App\Http\Controllers;

use Auth;
use App\Event;
use App\Task;
use App\Debt;
use Validator;
use App\Contact;
use App\Reminder;
use Carbon\Carbon;
use App\Http\Requests;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lastUpdatedContacts = Contact::where('account_id', Auth::user()->account_id)
                                    ->orderBy('updated_at', 'desc')
                                    ->limit(10)
                                    ->get();

        // Latest statistics
        $contacts = Contact::where('account_id', Auth::user()->account_id)
                                    ->get();

        if (count($contacts) == 0) {
            return view('dashboard.blank');
        }

        $number_of_contacts = $contacts->count();
        $number_of_reminders = 0;
        $number_of_notes = 0;
        $number_of_activities = 0;
        $number_of_gifts = 0;
        $number_of_tasks = 0;
        $number_of_kids = 0;
        $debt_owed = 0;
        $debt_due = 0;

        foreach ($contacts as $contact) {
            $number_of_reminders = $number_of_reminders + $contact->number_of_reminders;
            $number_of_notes = $number_of_notes + $contact->number_of_notes;
            $number_of_activities = $number_of_activities + $contact->number_of_activities;
            $number_of_gifts = $number_of_gifts + $contact->number_of_gifts_ideas;
            $number_of_gifts = $number_of_gifts + $contact->number_of_gifts_received;
            $number_of_gifts = $number_of_gifts + $contact->number_of_gifts_offered;
            $number_of_tasks = $number_of_tasks + $contact->number_of_tasks_in_progress;
            $number_of_tasks = $number_of_tasks + $contact->number_of_tasks_completed;
            $number_of_kids = $number_of_kids + $contact->number_of_kids;
        }

        $debts = Debt::where('account_id', Auth::user()->account_id)
                        ->where('status', 'inprogress')
                        ->where('in_debt', 'yes')
                        ->get();

        foreach ($debts as $debt) {
            $debt_due = $debt_due + $debt->amount;
        }

        $debts = Debt::where('account_id', Auth::user()->account_id)
                    ->where('status', 'inprogress')
                    ->where('in_debt', 'no')
                    ->get();

        foreach ($debts as $debt) {
            $debt_owed = $debt_owed + $debt->amount;
        }

        // List of events
        $events = Event::where('account_id', Auth::user()->account_id)
                      ->orderBy('created_at', 'desc')
                      ->limit(30)
                      ->get();

        $eventsArray = collect();

        foreach ($events as $event) {
            $contact = Contact::findOrFail($event->contact_id);

            $eventsArray->push([
                'id' => $event->id,
                'date' => DateHelper::createDateFromFormat($event->created_at, Auth::user()->timezone),
                'object_type' => $event->object_type,
                'object_id' => $event->object_id,
                'contact_id' => $contact->id,
                'contact_complete_name' => $contact->getCompleteName(),
                'nature_of_operation' => $event->nature_of_operation,
            ]);
        }

        // List of upcoming reminders
        $upcomingReminders = Reminder::where('account_id', Auth::user()->account_id)
                                    ->where('next_expected_date', '>', Carbon::now())
                                    ->orderBy('next_expected_date', 'asc')
                                    ->limit(10)
                                    ->get();

        // Active tasks
        $tasks = Task::where('account_id', Auth::user()->account_id)
                        ->where('status', 'inprogress')
                        ->get();

        // Debts
        $debts = Debt::where('account_id', Auth::user()->account_id)
                        ->where('status', 'inprogress')
                        ->get();

        $data = [
            'events' => $eventsArray,
            'lastUpdatedContacts' => $lastUpdatedContacts,
            'upcomingReminders' => $upcomingReminders,
            'number_of_contacts' => $number_of_contacts,
            'number_of_reminders' => $number_of_reminders,
            'number_of_notes' => $number_of_notes,
            'number_of_activities' => $number_of_activities,
            'number_of_gifts' => $number_of_gifts,
            'number_of_tasks' => $number_of_tasks,
            'number_of_kids' => $number_of_kids,
            'debt_due' => $debt_due,
            'debt_owed' => $debt_owed,
            'tasks' => $tasks,
            'debts' => $debts,
        ];

        return view('dashboard.index', $data);
    }
}
