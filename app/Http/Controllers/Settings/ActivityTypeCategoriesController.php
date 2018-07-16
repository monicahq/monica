<?php

namespace App\Http\Controllers\Settings;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Contact\ActivityTypeCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActivityTypeCategoriesController extends Controller
{
    /**
     * Get all the activity type categories.
     */
    public function index()
    {
        $activityTypeCategoriesData = collect([]);
        $activityTypeCategories = auth()->user()->account->activityTypeCategories;

        foreach ($activityTypeCategories as $activityTypeCategory) {
            $data = [
                'id' => $activityTypeCategory->id,
                'name' => $activityTypeCategory->name,
            ];
            $activityTypeCategoriesData->push($data);
        }

        return $activityTypeCategoriesData;
    }

    /**
     * Store the activity type category.
     */
    public function create(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        $activityTypeCategory = ActivityTypeCategory::create(
            $request->only([
                'name',
            ])
            + [
                'account_id' => auth()->user()->account->id,
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
    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        try {
            $activityTypeCategory = ActivityTypeCategory::where('account_id', auth()->user()->account_id)
                ->where('id', $request->get('id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return false;
        }

        $activityTypeCategory->update(
            $request->only([
                'name',
            ])
        );

        return $activityTypeCategory;
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
