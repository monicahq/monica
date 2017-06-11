<?php

namespace App\Http\Controllers;

use Auth;
use App\Kid;
use App\Note;
use App\Task;
use App\Event;
use Validator;
use App\Account;
use App\Contact;
use App\Activity;
use App\Reminder;
use Carbon\Carbon;
use App\Http\Requests;
use App\SignificantOther;
use Illuminate\Http\Request;
use App\Helpers\RandomHelper;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.index');
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'email' => 'required|email|max:2083',
        ]);

        if ($validator->fails()) {
            return redirect('/settings')
              ->withInput()
              ->withErrors($validator);
        }

        $email = $request->input('email');
        $timezone = $request->input('timezone');
        $layout = $request->input('layout');
        $locale = $request->input('locale');
        $currency = $request->input('currency_id');

        $user = Auth::user();
        $user->email = $email;
        $user->timezone = $timezone;
        $user->fluid_container = $layout;
        $user->metric = $layout;
        $user->locale = $locale;
        $user->currency_id = $currency;
        $user->save();

        return redirect('settings')->with('status', trans('settings.settings_success'));
    }

    public function delete()
    {
        // get the account id
        $accountID = Auth::user()->account_id;

        // delete all reminders
        $reminders = Reminder::where('account_id', $accountID)->get();
        foreach ($reminders as $reminder) {
            $reminder->forceDelete();
        }

        // delete contacts
        $contacts = Contact::where('account_id', $accountID)->get();
        foreach ($contacts as $contact) {
            $contact->forceDelete();
        }

        // delete kids
        $kids = Kid::where('account_id', $accountID)->get();
        foreach ($kids as $kid) {
            $kid->forceDelete();
        }

        // delete notes
        $notes = Note::where('account_id', $accountID)->get();
        foreach ($notes as $note) {
            $note->forceDelete();
        }

        // delete significant others
        $significantOthers = SignificantOther::where('account_id', $accountID)->get();
        foreach ($significantOthers as $significantOther) {
            $significantOther->forceDelete();
        }

        // delete tasks
        $tasks = Task::where('account_id', $accountID)->get();
        foreach ($tasks as $task) {
            $task->forceDelete();
        }

        // delete activities
        $activities = Activity::where('account_id', $accountID)->get();
        foreach ($activities as $activity) {
            $activity->forceDelete();
        }

        // delete events
        $events = Event::where('account_id', $accountID)->get();
        foreach ($events as $event) {
            $event->forceDelete();
        }

        // delete account
        $account = Account::find($accountID);
        $account->delete();

        Auth::user()->delete();

        return redirect('/');
    }
}
