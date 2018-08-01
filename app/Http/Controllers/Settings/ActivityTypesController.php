<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contact\ActivityType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActivityTypesController extends Controller
{
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
    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        try {
            $activityType = ActivityType::where('account_id', auth()->user()->account_id)
                ->findOrFail($request->get('id'));
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
     * Destroy an activity type.
     */
    public function destroy(Request $request, $activityTypeId)
    {
        try {
            $activityType = ActivityType::where('account_id', auth()->user()->account_id)
                ->findOrFail($activityTypeId);
        } catch (ModelNotFoundException $e) {
            return trans('settings.personalization_activity_type_modal_delete_error');
        }

        $activityType->resetAssociationWithActivities();
        $activityType->delete();
    }
}
