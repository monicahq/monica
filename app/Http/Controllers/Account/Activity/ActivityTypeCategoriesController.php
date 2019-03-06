<?php

namespace App\Http\Controllers\Account\Activity;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Account\Activity\ActivityTypeCategory\CreateActivityTypeCategory;
use App\Services\Account\Activity\ActivityTypeCategory\UpdateActivityTypeCategory;
use App\Services\Account\Activity\ActivityTypeCategory\DestroyActivityTypeCategory;
use App\Http\Resources\Activity\ActivityTypeCategory as ActivityTypeCategoryResource;

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
     * Store an activity type category.
     *
     * @param  Request $request
     * @return ActivityTypeCategoryResource
     */
    public function store(Request $request)
    {
        $type = app(CreateActivityTypeCategory::class)->execute([
            'account_id' => auth()->user()->account->id,
            'name' => $request->get('name'),
            'translation_key' => $request->get('translation_key'),
        ]);

        return new ActivityTypeCategoryResource($type);
    }

    /**
     * Update an activity type category.
     *
     * @param Request $request
     * @param int $activityTypeCategoryId
     * @return ActivityTypeCategoryResource
     */
    public function update(Request $request, $activityTypeCategoryId)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'activity_type_category_id' => $activityTypeCategoryId,
            'name' => $request->get('name'),
            'translation_key' => $request->get('translation_key'),
        ];

        $type = app(UpdateActivityTypeCategory::class)->execute($data);

        return new ActivityTypeCategoryResource($type);
    }

    /**
     * Delete the activity type category.
     *
     * @param Request $request
     * @param int $activityTypeCategoryId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $activityTypeCategoryId)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'activity_type_category_id' => $activityTypeCategoryId,
        ];

        try {
            app(DestroyActivityTypeCategory::class)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }

        return $this->respondObjectDeleted($activityTypeCategoryId);
    }
}
