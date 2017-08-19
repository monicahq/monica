<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Activity;
use App\Http\Controllers\Controller;
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
        return view('people.activities.index')
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
        return view('people.activities.add')
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
        $activity = $contact->activities()->create(
            $request->only([
                'summary',
                'date_it_happened',
                'activity_type_id',
                'description',
            ])
            + ['account_id' => $contact->account_id]
        );

        $contact->logEvent('activity', $activity->id, 'create');
        $contact->calculateActivitiesStatistics();

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
    public function edit(Contact $contact, Activity $activity)
    {
        return view('people.activities.edit')
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
    public function update(ActivitiesRequest $request, Contact $contact, Activity $activity)
    {
        $activity->update(
            $request->only([
                'summary',
                'date_it_happened',
                'activity_type_id',
                'description',
            ])
            + ['account_id' => $contact->account_id]
        );

        $contact->logEvent('activity', $activity->id, 'update');
        $contact->calculateActivitiesStatistics();

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
