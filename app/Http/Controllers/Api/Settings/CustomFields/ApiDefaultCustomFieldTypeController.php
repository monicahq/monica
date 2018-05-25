<?php

namespace App\Http\Controllers\Api\Settings\CustomFields;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Settings\CustomFields\DefaultCustomFieldType;
use App\Http\Resources\Settings\CustomField\DefaultCustomFieldType as DefaultCustomFieldTypeResource;

class ApiDefaultCustomFieldTypeController extends ApiController
{
    /**
     * Get the list of custom field types.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $defaultCustomFieldTypes = DefaultCustomFieldType::paginate($this->getLimitPerPage());

        return DefaultCustomFieldTypeResource::collection($defaultCustomFieldTypes);
    }

    /**
     * Get the detail of a given custom field type.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $defaultCustomFieldTypeId)
    {
        try {
            $defaultCustomFieldType = DefaultCustomFieldType::where('id', $defaultCustomFieldTypeId)
                                                            ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new DefaultCustomFieldTypeResource($defaultCustomFieldType);
    }
}
