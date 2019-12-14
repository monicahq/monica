<?php

namespace App\Http\Controllers;

use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Journal\JournalEntry;
use App\Http\Requests\People\ActivitiesRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActivitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function index(Contact $contact)
    {
        return view('activities.index')
            ->withContact($contact);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function create(Contact $contact)
    {
        return view('activities.add')
            ->withContact($contact)
            ->withActivity(new Activity);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ActivitiesRequest $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ActivitiesRequest $request, Contact $contact)
    {
        $specifiedContacts = $request->input('contacts');
        $specifiedContactsObj = [];

        try {
            // Test if every attached contact are found before creating the activity
            foreach ($specifiedContacts as $newContactId) {
                $newContact = Contact::where('account_id', $request->user()->account_id)
                    ->findOrFail($newContactId);
                array_push($specifiedContactsObj, $newContact);
            }
        } catch (ModelNotFoundException $e) {
            return redirect()->route('people.show', $contact)
                ->withErrors(trans('people.activities_add_error'));
        }

        $activity = Activity::create(
            $request->only([
                'summary',
                'date_it_happened',
                'activity_type_id',
                'description',
            ])
            + ['account_id' => $request->user()->account_id]
        );

        // New attendees
        foreach ($specifiedContactsObj as $newContact) {
            $newContact->activities()->attach($activity, ['account_id' => $request->user()->account_id]);
            $newContact->calculateActivitiesStatistics();
        }

        // Log a journal entry
        JournalEntry::add($activity);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.activities_add_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Activity $activity
     *
     * @return \Illuminate\View\View
     */
    public function edit(Activity $activity, Contact $contact)
    {
        return view('activities.edit')
            ->withContact($contact)
            ->withActivity($activity);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ActivitiesRequest $request
     * @param Contact $contact
     * @param Activity $activity
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ActivitiesRequest $request, Activity $activity, Contact $contact)
    {
        $user = $request->user();
        $account = $user->account;
        $specifiedContacts = $request->input('contacts');
        $specifiedContactsObj = [];

        try {
            // Test if every attached contact are found before updating the activity
            foreach ($specifiedContacts as $newContactId) {
                $specifiedContactsObj[$newContactId] = Contact::where('account_id', $account->id)
                    ->findOrFail($newContactId);
            }
        } catch (ModelNotFoundException $e) {
            return redirect()->route('people.show', $contact)
                ->withErrors(trans('people.activities_add_error'));
        }

        $activity->update(
            $request->only([
                'summary',
                'date_it_happened',
                'activity_type_id',
                'description',
            ])
            + ['account_id' => $account->id]
        );

        // Find existing attendees
        $existing = $activity->contacts()->get();

        foreach ($existing as $existingContact) {
            // Has an existing attendee been removed?
            if (! array_key_exists($existingContact->id, $specifiedContactsObj)) {
                $existingContact->activities()->detach($activity);
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the activity again
            unset($specifiedContactsObj[$existingContact->id]);

            $existingContact->calculateActivitiesStatistics();
        }

        // New attendees
        foreach ($specifiedContactsObj as $newContact) {
            $newContact->activities()->attach($activity, ['account_id' => $account->id]);
        }

        // Update the journal entry (in case date has changed)
        $activity->journalEntry->update([
            'date' => $activity->date_it_happened,
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.activities_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Activity $activity
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Activity $activity, Contact $contact)
    {
        $activity->deleteJournalEntry();

        foreach ($activity->contacts as $contactActivity) {
            $contactActivity->calculateActivitiesStatistics();
        }

        $activity->delete();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.activities_delete_success'));
    }
}
