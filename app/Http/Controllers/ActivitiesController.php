<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Activity;
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
    public function store(ActivitiesRequest $request)
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
            $contact = Contact::findOrFail($newContactId);
            $contact->activities()->save($activity);
            $contact->logEvent('activity', $activity->id, 'create');
            $contact->calculateActivitiesStatistics();
        }

        return redirect('/people/'.$contact->id)
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
    public function update(ActivitiesRequest $request, Activity $activity)
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

        foreach ($existing as $contact) {
            // Has an existing attendee been removed?
            if (! in_array($contact->id, $specifiedContacts)) {
                $contact->activities()->detach($activity);
                $contact->logEvent('activity', $activity->id, 'delete');
            } else {
                // Otherwise we're updating an activity that someone's
                // already a part of
                $contact->logEvent('activity', $activity->id, 'update');
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the activity again
            $idx = array_search($contact->id, $specifiedContacts);
            unset($specifiedContacts[$idx]);

            $contact->calculateActivitiesStatistics();
        }

        // New attendees
        foreach ($specifiedContacts as $newContactId) {
            $contact = Contact::findOrFail($newContactId);
            $contact->activities()->save($activity);
            $contact->logEvent('activity', $activity->id, 'create');
        }

        // Eventually we'll redirect to a dedicated 'view activity page', but for now
        // just show the last person added
        return redirect('/people/'.$contact->id)
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
        $activity->delete();

        $contact->events()->forObject($activity)->get()->each->delete();

        $contact->calculateActivitiesStatistics();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.activities_delete_success'));
    }
}
