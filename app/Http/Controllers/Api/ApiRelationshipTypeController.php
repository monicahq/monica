<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Relationship\RelationshipType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\RelationshipType\RelationshipType as RelationshipTypeResource;

class ApiRelationshipTypeController extends ApiController
{
    /**
     * Account ID column name.
     */
    const ACCOUNT_ID = 'account_id';

    /**
     * Get all relationship types in an instance.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $relationshipTypes = auth()->user()->account->relationshipTypes()
                                                        ->paginate($this->getLimitPerPage());
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return RelationshipTypeResource::collection($relationshipTypes);
    }

    /**
     * Get the detail of a given relationship type.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $relationshipType = RelationshipType::where(static::ACCOUNT_ID, auth()->user()->account_id)
                                                        ->where('id', $id)
                                                        ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new RelationshipTypeResource($relationshipType);
    }
}
