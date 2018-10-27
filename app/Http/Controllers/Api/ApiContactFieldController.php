<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\ContactField\ContactField as ContactFieldResource;

class ApiContactFieldController extends ApiController
{
    /**
     * Get the detail of a given contactField.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $contactField = ContactField::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ContactFieldResource($contactField);
    }

    /**
     * Store the contactField.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $contactField = $this->validateUpdate($request, $request->input('contact_field_type_id'));
        if (! $contactField instanceof ContactField) {
            return $contactField;
        }

        try {
            $contactField = ContactField::create(
              $request->all()
              + [
                'account_id' => auth()->user()->account_id,
              ]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ContactFieldResource($contactField);
    }

    /**
     * Update the contactField.
     * @param  Request $request
     * @param  int $contactFieldId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $contactFieldId)
    {
        $contactField = $this->validateUpdate($request, $contactFieldId);
        if (! $contactField instanceof ContactField) {
            return $contactField;
        }

        try {
            $contactField->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ContactFieldResource($contactField);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @param  int $contactFieldId
     * @return mixed
     */
    private function validateUpdate(Request $request, $contactFieldId)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'data' => 'max:255|required',
            'contact_field_type_id' => 'integer|required',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $contactField = ContactField::where('account_id', auth()->user()->account_id)
                ->where('id', $contactFieldId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return $contactField;
    }

    /**
     * Delete a contactField.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $contactFieldId)
    {
        try {
            $contactField = ContactField::where('account_id', auth()->user()->account_id)
                ->where('id', $contactFieldId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $contactField->delete();

        return $this->respondObjectDeleted($contactField->id);
    }

    /**
     * Get the list of contact fields for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function contactFields(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $contactFields = $contact->contactFields()
                ->paginate($this->getLimitPerPage());

        return ContactFieldResource::collection($contactFields);
    }
}
