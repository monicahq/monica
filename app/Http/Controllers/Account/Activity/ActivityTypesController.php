<?php

namespace App\Http\Controllers\Account\Activity;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Account\Activity\ActivityType\CreateActivityType;
use App\Services\Account\Activity\ActivityType\UpdateActivityType;
use App\Services\Account\Activity\ActivityType\DestroyActivityType;
use App\Http\Resources\Activity\ActivityType as ActivityTypeResource;

class ActivityTypesController extends Controller
{
    use JsonRespondController;

    /**
     * Store an activity type category.
     *
     * @param  Request $request
     * @return ActivityTypeResource
     */
    public function store(Request $request)
    {
        $type = app(CreateActivityType::class)->execute([
            'account_id' => auth()->user()->account->id,
            'activity_type_category_id' => $request->get('activity_type_category_id'),
            'name' => $request->get('name'),
            'translation_key' => $request->get('translation_key'),
        ]);

        return new ActivityTypeResource($type);
    }

    /**
     * Update an activity type.
     *
     * @param Request $request
     * @param int $activityTypeId
     * @return ActivityTypeResource
     */
    public function update(Request $request, $activityTypeId)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'activity_type_id' => $activityTypeId,
            'activity_type_category_id' => $request->get('activity_type_category_id'),
            'name' => $request->get('name'),
            'translation_key' => $request->get('translation_key'),
        ];

        $type = app(UpdateActivityType::class)->execute($data);

        return new ActivityTypeResource($type);
    }

    /**
     * Delete the activity type.
     *
     * @param Request $request
     * @param int $activityTypeId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $activityTypeId)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'activity_type_id' => $activityTypeId,
        ];

        try {
            app(DestroyActivityType::class)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }

        return $this->respondObjectDeleted($activityTypeId);
    }
}
