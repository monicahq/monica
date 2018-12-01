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

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Relationship\RelationshipType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\RelationshipType\RelationshipType as RelationshipTypeResource;

class ApiRelationshipTypeController extends ApiController
{
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
            $relationshipType = RelationshipType::where('account_id', auth()->user()->account_id)
                                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new RelationshipTypeResource($relationshipType);
    }
}
