<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\RelationshipTypeGroup\RelationshipTypeGroup as RelationshipTypeGroupResource;

class ApiRelationshipTypeGroupController extends ApiController
{
    /**
     * Account ID column name.
     */
    const ACCOUNT_ID = 'account_id';

    /**
     * Get all relationship type groups in an instance.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $relationshipTypeGroups = auth()->user()->account->relationshipTypeGroups()
                                                            ->paginate($this->getLimitPerPage());
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return RelationshipTypeGroupResource::collection($relationshipTypeGroups);
    }

    /**
     * Get the detail of a given relationship type group.
     *
     * @param Request $request
     *
     * @return RelationshipTypeGroupResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $relationshipTypeGroup = RelationshipTypeGroup::where(static::ACCOUNT_ID, auth()->user()->account_id)
                                                            ->where('id', $id)
                                                            ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new RelationshipTypeGroupResource($relationshipTypeGroup);
    }
}
