<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\RemindersRequest;

class RemindersController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function create(Contact $contact)
    {
        return view('people.reminders.add')
            ->withContact($contact)
            ->withReminder(new Reminder);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param RemindersRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(RemindersRequest $request, Contact $contact)
    {
        $reminder = $contact->reminders()->create(
            $request->only([
                'title',
                'description',
                'frequency_type',
                'next_expected_date',
                'frequency_number',
            ])
            + ['account_id' => $contact->account_id]
        );

        $reminder->scheduleNotifications();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_create_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Reminder $reminder
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Reminder $reminder)
    {
        return view('people.reminders.edit')
            ->withContact($contact)
            ->withReminder($reminder);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RemindersRequest $request
     * @param Contact $contact
     * @param Reminder $reminder
     * @return \Illuminate\Http\Response
     */
    public function update(RemindersRequest $request, Contact $contact, Reminder $reminder)
    {
        $reminder->update(
            $request->only([
                'title',
                'next_expected_date',
                'description',
                'frequency_type',
                'frequency_number',
            ])
            + ['account_id' => $contact->account_id]
        );

        $reminder->purgeNotifications();
        $reminder->scheduleNotifications();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Reminder $reminder
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Reminder $reminder)
    {
        if ($reminder->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        $reminder->purgeNotifications();
        $reminder->delete();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_delete_success'));
    }
}
