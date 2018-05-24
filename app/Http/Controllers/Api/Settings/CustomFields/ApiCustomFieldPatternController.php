<?php

namespace App\Http\Controllers\Api\Settings\CustomFields;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Models\Settings\CustomFields\CustomFieldPattern;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Settings\CustomField\CustomFieldPattern as CustomFieldPatternResource;

class ApiCustomFieldPatternController extends ApiController
{
    /**
     * Get the list of custom fields.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $customFieldPatternPatterns = auth()->user()->account->customFieldPatterns()
                                    ->paginate($this->getLimitPerPage());

        return CustomFieldPatternResource::collection($customFieldPatternPatterns);
    }

    /**
     * Get the detail of a given contact field type.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $customFieldPatternId)
    {
        try {
            $customFieldPattern = CustomFieldPattern::where('account_id', auth()->user()->account_id)
                ->where('id', $customFieldPatternId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CustomFieldPatternResource($customFieldPattern);
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
            $customFieldPattern = CustomFieldPattern::create(
                $request->all()
                + ['account_id' => auth()->user()->account->id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new CustomFieldPatternResource($customFieldPattern);
    }

    /**
     * Update the custom field type.
     *
     * @param  Request $request
     * @param  int $customFieldPatternTypeId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $customFieldPatternTypeId)
    {
        try {
            $customFieldPattern = CustomFieldPattern::where('account_id', auth()->user()->account_id)
                ->where('id', $customFieldPatternTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isValid = $this->validateQuery($request);

        if ($isValid !== true) {
            return $isValid;
        }

        try {
            $customFieldPattern->update(
                $request->all()
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new CustomFieldPatternResource($customFieldPattern);
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
            'icon_name' => 'string|max:255|required',
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
    public function destroy(Request $request, $customFieldPatternId)
    {
        try {
            $customFieldPattern = CustomFieldPattern::where('account_id', auth()->user()->account_id)
                ->where('id', $customFieldPatternId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $customFieldPattern->delete();

        return $this->respondObjectDeleted($customFieldPattern->id);
    }
}
