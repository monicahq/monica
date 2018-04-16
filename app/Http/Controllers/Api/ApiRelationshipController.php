<?php

namespace App\Http\Controllers\Api;

use App\Relationship;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Relationship\Relationship as RelationshipResource;

class ApiRelationshipController extends ApiController
{
    /**
     * Get the detail of a given relationship.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $relationship = Relationship::where('account_id', auth()->user()->account_id)
                            ->where('id', $id)
                            ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new RelationshipResource($relationship);
    }
}
