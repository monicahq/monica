<?php

namespace App\Http\Controllers\Api\Settings\CustomFields;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Settings\CustomField\CustomField as CustomFieldResource;

class ApiCustomFieldController extends ApiController
{
    /**
     * Get the list of custom fields.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $customFields = auth()->user()->account->customFields()
                                    ->paginate($this->getLimitPerPage());

        return CustomFieldResource::collection($customFields);
    }

    /**
     * Get the detail of a given contact field type.
     *
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
     * Store the custom field.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $isValid = $this->validateQuery($request);
        if ($isValid !== true) {
            return $isValid;
        }

        try {
            $customField = CustomField::create(
                $request->all()
                + ['account_id' => auth()->user()->account->id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new CustomFieldResource($customField);
    }

    /**
     * Update the custom field type.
     *
     * @param  Request $request
     * @param  int $customFieldTypeId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $customFieldTypeId)
    {
        try {
            $customField = CustomField::where('account_id', auth()->user()->account_id)
                ->where('id', $customFieldTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isValid = $this->validateQuery($request);

        if ($isValid !== true) {
            return $isValid;
        }

        try {
            $customField->update(
                $request->all()
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new CustomFieldResource($customField);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateQuery(Request $request)
    {
        // Validates basic fields to create or edit the entry
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'is_list' => 'boolean|required',
            'is_important' => 'boolean|required',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->setHTTPStatusCode(400)
                        ->respondWithError($validator->errors()->all());
        }

        return true;
    }

    /**
     * Delete a custom field.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $customFieldId)
    {
        try {
            $customField = CustomField::where('account_id', auth()->user()->account_id)
                ->where('id', $customFieldId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $customField->delete();

        return $this->respondObjectDeleted($customField->id);
    }
}
