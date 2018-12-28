<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\AvatarHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;

class RemindersController extends Controller
{
    /**
     * Show the form for creating a new reminder.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function create(Contact $contact)
    {
        return view('people.reminders.add')
            ->withContact($contact)
            ->withAvatar(AvatarHelper::get($contact, 87))
            ->withReminder(new Reminder);
    }

    /**
     * Store a reminder.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Contact $contact)
    {
        return (new CreateReminder)->execute([
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'initial_date' => $request->get('next_expected_date'),
            'frequency_type' => $request->get('frequency_type'),
            'frequency_number' => $request->get('frequency_number'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
        ]);

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
            ->withAvatar(AvatarHelper::get($contact, 87))
            ->withReminder($reminder);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Contact $contact
     * @param Reminder $reminder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact, Reminder $reminder)
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
