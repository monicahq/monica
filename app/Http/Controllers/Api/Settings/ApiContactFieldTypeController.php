<?php

namespace App\Http\Controllers\Api\Settings;

use Validator;
use App\ContactFieldType;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Settings\ContactFieldType\ContactFieldType as ContactFieldTypeResource;

class ApiContactFieldTypeController extends ApiController
{
    /**
     * Get the list of contact field types.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $contactFieldTypes = auth()->user()->account->contactFieldTypes()
                                    ->paginate($this->getLimitPerPage());

        return ContactFieldTypeResource::collection($contactFieldTypes);
    }

    /**
     * Get the detail of a given contact field type.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $contactFieldTypeId)
    {
        try {
            $contactFieldType = ContactFieldType::where('account_id', auth()->user()->account_id)
                ->where('id', $contactFieldTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ContactFieldTypeResource($contactFieldType);
    }

    /**
     * Store the contactfieldtype.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'fontawesome_icon' => 'nullable|max:255',
            'protocol' => 'nullable|max:255',
            'delible' => 'integer',
            'type' => 'nullable|max:255',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contactFieldType = ContactFieldType::create(
                $request->all()
                + ['account_id' => auth()->user()->account->id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ContactFieldTypeResource($contactFieldType);
    }

    /**
     * Update the contact field type .
     * @param  Request $request
     * @param  int $contactfieldtypeId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $contactfieldtypeId)
    {
        try {
            $contactfieldtype = contactfieldtype::where('account_id', auth()->user()->account_id)
                ->where('id', $contactfieldtypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'summary' => 'required|max:100000',
            'description' => 'required|max:1000000',
            'date_it_happened' => 'required|date',
            'contactfieldtype_type_id' => 'integer',
            'contacts' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        // Make sure each contact exists and has the right to be associated with
        // this account
        $attendeesID = $request->get('contacts');
        foreach ($attendeesID as $attendeeID) {
            try {
                $contact = Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $attendeeID)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }
        }

        // Update the contactfieldtype itself
        try {
            $contactfieldtype->update(
                $request->only([
                    'summary',
                    'date_it_happened',
                    'contactfieldtype_type_id',
                    'description',
                ])
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        // Get the attendees
        $attendees = $request->get('contacts');

        // Find existing contacts
        $existing = $contactfieldtype->contacts()->get();

        foreach ($existing as $contact) {
            // Has an existing attendee been removed?
            if (! in_array($contact->id, $attendees)) {
                $contact->activities()->detach($contactfieldtype);
                $contact->logEvent('contactfieldtype', $contactfieldtype->id, 'delete');
            } else {
                // Otherwise we're updating an contactfieldtype that someone's
                // already a part of
                $contact->logEvent('contactfieldtype', $contactfieldtype->id, 'update');
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the contactfieldtype again
            $idx = array_search($contact->id, $attendees);
            unset($attendees[$idx]);

            $contact->calculateActivitiesStatistics();
        }

        // New attendees
        foreach ($attendees as $newContactId) {
            $contact = Contact::findOrFail($newContactId);
            $contact->activities()->save($contactfieldtype);
            $contact->logEvent('contactfieldtype', $contactfieldtype->id, 'create');
        }

        return new contactfieldtypeResource($contactfieldtype);
    }

    /**
     * Delete an contactfieldtype.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $contactfieldtypeId)
    {
        try {
            $contactfieldtype = Note::where('account_id', auth()->user()->account_id)
                ->where('id', $contactfieldtypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $contactfieldtype->delete();

        return $this->respondObjectDeleted($contactfieldtype->id);
    }

    /**
     * Get the list of activities for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function activities(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $activities = $contact->activities()
                ->paginate($this->getLimitPerPage());

        return contactfieldtypeResource::collection($activities);
    }

    /**
     * Get the list of all contactfieldtype types.
     *
     * @return \Illuminate\Http\Response
     */
    public function contactfieldtypetypes(Request $request)
    {
        $activities = contactfieldtypeType::all();

        return contactfieldtypeTypeResource::collection($activities);
    }
}
