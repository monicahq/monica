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
        $account = Auth::user()->account;

        $lastUpdatedContacts = $account->contacts()->limit(10)->get();

        // Latest statistics
        if ($account->contacts()->count() === 0) {
            return view('dashboard.blank');
        }

        $number_of_contacts = $account->contacts()->count();
        $number_of_reminders = $account->reminders()->count();
        $number_of_notes = $account->notes()->count();
        $number_of_activities = $account->activities()->count();
        $number_of_gifts = $account->gifts()->count();
        $number_of_tasks = $account->tasks()->count();
        $number_of_kids = $account->kids()->count();

        $debt = $account->debts->where('status', 'inprogress');

        $debt_due = $debt->where('in_debt', 'yes')
            ->reduce(function ($totalDueDebt, Debt $debt) {
                return $totalDueDebt + $debt->amount;
            }, 0);

        $debt_owed = $debt->where('in_debt', 'no')
            ->reduce(function ($totalOwedDebt, Debt $debt) {
                return $totalOwedDebt + $debt->amount;
            }, 0);

        // List of events
        $events = $account->events()->with('contact.significantOthers', 'contact.kids')->limit(30)->get()
            ->reject(function (Event $event) {
                return $event->contact === null;
            })
            ->map(function (Event $event) use ($account) {

                if ($event->object_type === 'significantother') {
                    $object = $event->contact->significantOthers->where('id', $event->object_id)->first();
                } elseif ($event->object_type === 'kid') {
                    $object = $event->contact->kids->where('id', $event->object_id)->first();
                }

                return [
                    'id' => $event->id,
                    'date' => DateHelper::createDateFromFormat($event->created_at, Auth::user()->timezone),
                    'object' => $object ?? null,
                    'object_type' => $event->object_type,
                    'object_id' => $event->object_id,
                    'contact_id' => $event->contact->id,
                    'contact_complete_name' => $event->contact->getCompleteName(),
                    'nature_of_operation' => $event->nature_of_operation,
                ];
            });

        // List of upcoming reminders
        $upcomingReminders = $account->reminders()
            ->where('next_expected_date', '>', Carbon::now())
            ->orderBy('next_expected_date', 'asc')
            ->limit(10)
            ->get();

        // Active tasks
        $tasks = $account->tasks->where('status', 'inprogress');

        $data = [
            'events' => $events,
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
            'debts' => $debt,
        ];

        return view('dashboard.index', $data);
    }
}
