<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RelationshipType\RelationshipType as RelationshipTypeResource;
use App\Models\Relationship\RelationshipType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ApiRelationshipTypeController extends ApiController
{
    /**
     * Get all relationship types in an instance.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     * @param Request $request
     *
     * @return RelationshipTypeResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $relationshipType = RelationshipType::where('account_id', auth()->user()->account_id)
                                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new RelationshipTypeResource($relationshipType);
    }
}
