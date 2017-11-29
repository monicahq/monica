<?php

namespace App\Http\Controllers;

use Auth;
use App\Debt;
use App\Event;
use App\Contact;
use Carbon\Carbon;
use App\Helpers\DateHelper;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $account = Auth::user()->account()
            ->withCount(
                'contacts', 'reminders', 'notes', 'activities', 'gifts', 'tasks'
            )->with('debts.contact')
            ->first();

        $lastUpdatedContacts = $account->contacts()->latest('updated_at')->limit(10)->get();

        // Latest statistics
        if ($account->contacts()->count() === 0) {
            return view('dashboard.blank');
        }

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
        $events = $account->events()->limit(30)->get()
            ->reject(function (Event $event) {
                return $event->contact === null;
            })
            ->map(function (Event $event) use ($account) {
                return [
                    'id' => $event->id,
                    'date' => DateHelper::createDateFromFormat($event->created_at, auth()->user()->timezone),
                    'object' => $object ?? null,
                    'contact_id' => $event->contact->id,
                    'object_type' => $event->object_type,
                    'object_id' => $event->object_id,
                    'contact_complete_name' => $event->contact->getCompleteName(auth()->user()->name_order),
                    'nature_of_operation' => $event->nature_of_operation,
                ];
            });

        // List of upcoming reminders
        $upcomingReminders = $account->reminders()
            ->where('next_expected_date', '>', Carbon::now())
            ->orderBy('next_expected_date', 'asc')
            ->with('contact')
            ->limit(10)
            ->get();

        // Active tasks
        $tasks = $account->tasks()->with('contact')->where('completed', 0)->get();

        $data = [
            'events' => $events,
            'lastUpdatedContacts' => $lastUpdatedContacts,
            'upcomingReminders' => $upcomingReminders,
            'number_of_contacts' => $account->contacts_count,
            'number_of_reminders' => $account->reminders_count,
            'number_of_notes' => $account->notes_count,
            'number_of_activities' => $account->activities_count,
            'number_of_gifts' => $account->gifts_count,
            'number_of_tasks' => $account->tasks_count,
            'debt_due' => $debt_due,
            'debt_owed' => $debt_owed,
            'tasks' => $tasks,
            'debts' => $debt,
            'user' => auth()->user(),
        ];

        return view('dashboard.index', $data);
    }
}
