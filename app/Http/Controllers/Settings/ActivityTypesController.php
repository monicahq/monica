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

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contact\ActivityType;
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Validator;
use App\Models\Contact\ActivityTypeCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActivityTypesController extends Controller
{
    use JsonRespondController;

    /**
     * Store the activity type.
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
            'activity_type_category_id' => 'required|integer',
        ])->validate();

        try {
            ActivityTypeCategory::where('account_id', auth()->user()->account_id)
                ->findOrFail($request->get('activity_type_category_id'));
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $activityType = ActivityType::create(
            $request->only([
                'name',
                'activity_type_category_id',
            ])
            + [
                'account_id' => auth()->user()->account_id,
            ]
        );

        return [
            'id' => $activityType->id,
            'name' => $activityType->name,
            'activity_type_category_id' => $activityType->activity_type_category_id,
        ];
    }

    /**
     * Update the given activity type.
     */
    public function update(Request $request, ActivityType $activitytype)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        $activitytype->update(
            $request->only([
                'name',
            ])
        );

        return $activitytype;
    }

    /**
     * Destroy an activity type.
     */
    public function destroy(Request $request, ActivityType $activitytype)
    {
        $activitytype->resetAssociationWithActivities();
        $activitytype->delete();

        return $this->respondObjectDeleted($activitytype->id);
    }
}
