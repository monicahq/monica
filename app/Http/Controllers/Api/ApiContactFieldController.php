<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\ContactField\CreateContactField;
use App\Services\Contact\ContactField\UpdateContactField;
use App\Services\Contact\ContactField\DestroyContactField;
use App\Http\Resources\ContactField\ContactField as ContactFieldResource;

class ApiContactFieldController extends ApiController
{
    /**
     * Get the detail of a given contactField.
     *
     * @param Request $request
     * @param int $contactFieldId
     *
     * @return ContactFieldResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $contactFieldId)
    {
        try {
            $contactField = ContactField::where('account_id', auth()->user()->account_id)
                ->findOrFail($contactFieldId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ContactFieldResource($contactField);
    }

    /**
     * Store the contactField.
     *
     * @param Request $request
     *
     * @return ContactFieldResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $contactField = app(CreateContactField::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactFieldResource($contactField);
    }

    /**
     * Update the contactField.
     *
     * @param Request $request
     * @param int $contactFieldId
     *
     * @return ContactFieldResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $contactFieldId)
    {
        try {
            $contactField = app(UpdateContactField::class)->execute(
                $request->except(['account_id', 'address_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'contact_field_id' => $contactFieldId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactFieldResource($contactField);
    }

    /**
     * Delete a contactField.
     *
     * @param Request $request
     * @param int $contactFieldId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $contactFieldId)
    {
        try {
            app(DestroyContactField::class)->execute([
                'account_id' => auth()->user()->account_id,
                'contact_field_id' => $contactFieldId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $contactFieldId);
    }

    /**
     * Get the list of contact fields for the given contact.
     *
     * @param Request $request
     * @param int $contactId
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
