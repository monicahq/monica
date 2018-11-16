<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AvatarHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use App\Services\Task\CreateTask;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TasksController extends Controller
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
     * Store a newly created resource in storage.
     *
     * @param $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return (new CreateTask)->execute([
            'account_id' => auth()->user()->account->id,
            'title' => $request->get('title'),
            'description' => ($request->get('description') == '' ? null : $request->get('description')),
        ]);
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
            ->withAvatar(AvatarHelper::get($contact, 87))
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
        $specifiedContacts = $request->get('contacts');
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
            $newContact->activities()->save($activity);
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.activities_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Activity $activity
     * @return \Illuminate\Http\Response
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
