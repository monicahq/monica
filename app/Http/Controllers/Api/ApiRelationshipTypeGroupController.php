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
     * @param  Request $request
     * @return \Illuminate\Http\Response
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
     * @param  Request $request
     * @return \Illuminate\Http\Response
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
