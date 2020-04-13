<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Http\Controllers\Controller;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Contact\Reminder\UpdateReminder;
use App\Services\Contact\Reminder\DestroyReminder;

class RemindersController extends Controller
{
    /**
     * Show the form for creating a new reminder.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function create(Contact $contact)
    {
        return view('people.reminders.add')
            ->withContact($contact)
            ->withAccountHasLimitations(AccountHelper::hasLimitations(auth()->user()->account))
            ->withReminder(new Reminder);
    }

    /**
     * Store a reminder.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Contact $contact)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'initial_date' => $request->input('initial_date'),
            'frequency_type' => $request->input('frequency_type'),
            'frequency_number' => is_null($request->input('frequency_number')) ? 1 : $request->input('frequency_number'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ];

        app(CreateReminder::class)->execute($data);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_create_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Reminder $reminder
     *
     * @return \Illuminate\View\View
     */
    public function edit(Contact $contact, Reminder $reminder)
    {
        return view('people.reminders.edit')
            ->withContact($contact)
            ->withAccountHasLimitations(AccountHelper::hasLimitations(auth()->user()->account))
            ->withReminder($reminder);
    }

    /**
     * Update the reminder.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Contact $contact, Reminder $reminder)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'reminder_id' => $reminder->id,
            'initial_date' => $request->input('initial_date'),
            'frequency_type' => $request->input('frequency_type'),
            'frequency_number' => is_null($request->input('frequency_number')) ? 1 : $request->input('frequency_number'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ];

        app(UpdateReminder::class)->execute($data);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_update_success'));
    }

    /**
     * Destroy the reminder.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Reminder $reminder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, Contact $contact, Reminder $reminder)
    {
        $data = [
            'account_id' => $reminder->account_id,
            'reminder_id' => $reminder->id,
        ];

        app(DestroyReminder::class)->execute($data);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_delete_success'));
    }
}
