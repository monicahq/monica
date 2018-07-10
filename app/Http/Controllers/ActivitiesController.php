<?php

namespace App\Http\Controllers;

use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use App\Models\Journal\JournalEntry;
use App\Http\Requests\People\ActivitiesRequest;

class ActivitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function store(ActivitiesRequest $request, Contact $contact)
    {
        $user = $request->user();
        $account = $user->account;

        $activity = Activity::create(
            $request->only([
                'summary',
                'date_it_happened',
                'activity_type_id',
                'description',
            ])
            + ['account_id' => $account->id]
        );

        // New attendees
        $specifiedContacts = $request->get('contacts');
        foreach ($specifiedContacts as $newContactId) {
            $newContact = Contact::findOrFail($newContactId);
            $newContact->activities()->attach($activity, ['account_id' => $newContact->account_id]);
            $newContact->logEvent('activity', $activity->id, 'create');
            $newContact->calculateActivitiesStatistics();
        }

        // Log a journal entry
        (new JournalEntry)->add($activity);

        return redirect('/people/'.$contact->hashID())
            ->with('success', trans('people.activities_add_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Contact $contact
     * @param Activity $activity
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact, Activity $activity)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Activity $activity
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function update(ActivitiesRequest $request, Activity $activity, Contact $contact)
    {
        $user = $request->user();
        $account = $user->account;

        $activity->update(
            $request->only([
                'summary',
                'date_it_happened',
                'activity_type_id',
                'description',
            ])
            + ['account_id' => $account->id]
        );

        // Who did we send through via the form?
        $specifiedContacts = $request->get('contacts');

        // Find existing attendees
        $existing = $activity->contacts()->get();

        foreach ($existing as $existingContact) {
            // Has an existing attendee been removed?
            if (! in_array($existingContact->id, $specifiedContacts)) {
                $existingContact->activities()->detach($activity);
                $existingContact->logEvent('activity', $activity->id, 'delete');
            } else {
                // Otherwise we're updating an activity that someone's
                // already a part of
                $existingContact->logEvent('activity', $activity->id, 'update');
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the activity again
            $idx = array_search($existingContact->id, $specifiedContacts);
            unset($specifiedContacts[$idx]);

            $existingContact->calculateActivitiesStatistics();
        }

        // New attendees
        foreach ($specifiedContacts as $newContactId) {
            $newContact = Contact::findOrFail($newContactId);
            $newContact->activities()->save($activity);
            $newContact->logEvent('activity', $activity->id, 'create');
        }

        return redirect('/people/'.$contact->hashID())
            ->with('success', trans('people.activities_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Activity $activity
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Activity $activity)
    {
        $activity->deleteJournalEntry();

        $activity->delete();

        $contact->events()->forObject($activity)->get()->each->delete();

        $contact->calculateActivitiesStatistics();

        return redirect('/people/'.$contact->hashID())
            ->with('success', trans('people.activities_delete_success'));
    }
}
