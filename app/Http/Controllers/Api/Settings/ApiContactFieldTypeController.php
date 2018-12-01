<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Models\Contact\ContactFieldType;
use Illuminate\Support\Facades\Validator;
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
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $contactFieldType = ContactFieldType::create(
                $request->all()
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ContactFieldTypeResource($contactFieldType);
    }

    /**
     * Update the contact field type.
     * @param  Request $request
     * @param  int
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $contactFieldTypeId)
    {
        try {
            $contactFieldType = ContactFieldType::where('account_id', auth()->user()->account_id)
                ->where('id', $contactFieldTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        // Update the contactfieldtype itself
        try {
            $contactFieldType->update(
                $request->only([
                    'name',
                    'fontawesome_icon',
                    'protocol',
                    'delible',
                    'type',
                ])
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ContactFieldTypeResource($contactFieldType);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateUpdate(Request $request)
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
            return $this->respondValidatorFailed($validator);
        }

        return true;
    }

    /**
     * Delete an contactfieldtype.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $contactFieldTypeId)
    {
        try {
            $contactFieldType = ContactFieldType::where('account_id', auth()->user()->account_id)
                ->where('id', $contactFieldTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $contactFields = auth()->user()->account->contactFields
                                ->where('contact_field_type_id', $contactFieldTypeId);

        foreach ($contactFields as $contactField) {
            $contactField->delete();
        }

        $contactFieldType->delete();

        return $this->respondObjectDeleted($contactFieldType->id);
    }
}
