<?php

namespace App\Http\Controllers\Api\Settings\CustomFields;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Models\Settings\CustomFields\Field;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Settings\CustomField\Field as FieldResource;

class ApiFieldController extends ApiController
{
    /**
     * Get the list of fields.
     * @todo : fields for a given custom field
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $fields = auth()->user()->account->fields()
                                ->paginate($this->getLimitPerPage());

        return FieldResource::collection($fields);
    }

    /**
     * Get the detail of a given field.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $field)
    {
        try {
            $field = Field::where('account_id', auth()->user()->account_id)
                            ->where('id', $field)
                            ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new FieldResource($field);
    }

    /**
     * Store the field.
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
            $field = Field::create(
                $request->all()
                + ['account_id' => auth()->user()->account->id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new FieldResource($field);
    }

    /**
     * Update the field type.
     *
     * @param  Request $request
     * @param  int $field
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $field)
    {
        try {
            $field = Field::where('account_id', auth()->user()->account_id)
                            ->where('id', $field)
                            ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isValid = $this->validateQuery($request);

        if ($isValid !== true) {
            return $isValid;
        }

        try {
            $field->update(
                $request->all()
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new FieldResource($field);
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
            'name' => 'string|required|max:255',
            'description' => 'string|max:255',
            'required' => 'boolean|required',
            'custom_field_id' =>  'integer|required',
            'custom_field_type' => 'string|required',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->setHTTPStatusCode(400)
                        ->respondWithError($validator->errors()->all());
        }

        return true;
    }

    /**
     * Delete a field.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $field)
    {
        try {
            $field = Field::where('account_id', auth()->user()->account_id)
                            ->where('id', $field)
                            ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $field->delete();

        return $this->respondObjectDeleted($field->id);
    }
}
