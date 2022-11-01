<?php

namespace App\Domains\Settings\ManageActivityTypes\Web\Controllers;

use App\Domains\Settings\ManageActivityTypes\Services\CreateActivity;
use App\Domains\Settings\ManageActivityTypes\Services\DestroyActivity;
use App\Domains\Settings\ManageActivityTypes\Services\UpdateActivity;
use App\Domains\Settings\ManageActivityTypes\Web\ViewHelpers\PersonalizeActivityTypesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeActivitiesController extends Controller
{
    public function store(Request $request, int $activityTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'activity_type_id' => $activityTypeId,
            'label' => $request->input('label'),
        ];

        $activity = (new CreateActivity())->execute($data);

        return response()->json([
            'data' => PersonalizeActivityTypesIndexViewHelper::dtoActivity($activity->activityType, $activity),
        ], 201);
    }

    public function update(Request $request, int $activityTypeId, int $activityId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'activity_type_id' => $activityTypeId,
            'activity_id' => $activityId,
            'label' => $request->input('label'),
        ];

        $activity = (new UpdateActivity())->execute($data);

        return response()->json([
            'data' => PersonalizeActivityTypesIndexViewHelper::dtoActivity($activity->activityType, $activity),
        ], 200);
    }

    public function destroy(Request $request, int $activityTypeId, int $activityId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'activity_type_id' => $activityTypeId,
            'activity_id' => $activityId,
        ];

        (new DestroyActivity())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
