<?php

namespace App\Http\Controllers\Settings;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contact\ActivityType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActivityTypesController extends Controller
{
    /**
     * Get all the activity types.
     */
    public function index(Request $request, $activityTypeCategoryId)
    {
        $activityTypesData = collect([]);
        $activityTypes = auth()->user()->account->activityTypes()
                                ->where('activity_type_category_id', $activityTypeCategoryId)
                                ->get();

        foreach ($activityTypes as $activityType) {
            $data = [
                'id' => $activityType->id,
                'name' => $activityType->name,
                'location_type' => $activityType->location_type,
            ];
            $activityTypesData->push($data);
        }

        return $activityTypesData;
    }

    /**
     * Store the activity type.
     */
    public function create(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
            'activity_type_category_id' => 'required|integer',
        ])->validate();

        $activityType = ActivityType::create(
            $request->only([
                'name',
                'activity_type_category_id',
            ])
            + [
                'account_id' => auth()->user()->account->id,
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
    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        try {
            $activityType = ActivityType::where('account_id', auth()->user()->account_id)
                ->where('id', $request->get('id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return false;
        }

        $activityType->update(
            $request->only([
                'name',
            ])
        );

        return $activityType;
    }

    /**
     * Destroy a gender type.
     */
    public function destroyAndReplaceGender(GendersRequest $request, Gender $genderToDelete, $genderToReplaceWithId)
    {
        try {
            $genderToReplaceWith = Gender::where('account_id', auth()->user()->account_id)
                ->where('id', $genderToReplaceWithId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new Exception(trans('settings.personalization_genders_modal_error'));
        }

        // We get the new gender to associate the contacts with.
        auth()->user()->account->replaceGender($genderToDelete, $genderToReplaceWith);

        $genderToDelete->delete();
    }

    /**
     * Destroy a gender type.
     */
    public function destroyGender(GendersRequest $request, Gender $gender)
    {
        $gender->delete();
    }
}
