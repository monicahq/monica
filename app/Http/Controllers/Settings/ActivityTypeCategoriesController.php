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
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Validator;
use App\Models\Contact\ActivityTypeCategory;

class ActivityTypeCategoriesController extends Controller
{
    use JsonRespondController;

    /**
     * Get all the activity type categories.
     */
    public function index()
    {
        $activityTypeCategoriesData = collect([]);
        $activityTypeCategories = auth()->user()->account->activityTypeCategories;

        foreach ($activityTypeCategories as $activityTypeCategory) {
            $activityTypesData = collect([]);
            $activityTypes = $activityTypeCategory->activityTypes;

            foreach ($activityTypes as $activityType) {
                $dataActivityType = [
                    'id' => $activityType->id,
                    'name' => $activityType->name,
                ];
                $activityTypesData->push($dataActivityType);
            }

            $data = [
                'id' => $activityTypeCategory->id,
                'name' => $activityTypeCategory->name,
                'activityTypes' => $activityTypesData,
            ];
            $activityTypeCategoriesData->push($data);
        }

        return $activityTypeCategoriesData;
    }

    /**
     * Store the activity type category.
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        $activityTypeCategory = ActivityTypeCategory::create(
            $request->only([
                'name',
            ])
            + [
                'account_id' => auth()->user()->account_id,
            ]
        );

        return [
            'id' => $activityTypeCategory->id,
            'name' => $activityTypeCategory->name,
        ];
    }

    /**
     * Update the given activity type category.
     */
    public function update(Request $request, ActivityTypeCategory $activitytypecategory)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        $activitytypecategory->update(
            $request->only([
                'name',
            ])
        );

        return $activitytypecategory;
    }

    /**
     * Destroy an activity type category.
     */
    public function destroy(Request $request, ActivityTypeCategory $activitytypecategory)
    {
        $activitytypecategory->deleteAllAssociatedActivityTypes();
        $activitytypecategory->delete();

        return $this->respondObjectDeleted($activitytypecategory->id);
    }
}
