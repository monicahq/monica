<?php

namespace App\Http\Controllers;

use Auth;
use App\Event;
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

        // List of events
        $events = Event::where('account_id', Auth::user()->account_id)
                      ->orderBy('created_at', 'desc')
                      ->limit(30)
                      ->get();

        if (count($events) == 0) {
            return view('dashboard.blank');
        }

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
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        $upcomingReminders = Reminder::where('account_id', Auth::user()->account_id)
                                    ->where('next_expected_date', '<', $thirtyDaysFromNow)
                                    ->get();

        $data = [
            'events' => $eventsArray,
            'lastUpdatedContacts' => $lastUpdatedContacts,
            'upcomingReminders' => $upcomingReminders,
        ];

        return view('dashboard.index', $data);
    }
}
