<?php

namespace App\Http\Controllers\Account\LifeEvent;

use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;

class LifeEventCategoriesController extends Controller
{
    use JsonRespondController;

    /**
     * Get all the life event categories.
     */
    public function index()
    {
        $lifeEventCategoriesData = collect([]);
        $lifeEventCategories = auth()->user()->account->lifeEventCategories;

        foreach ($lifeEventCategories as $lifeEventCategory) {
            $lifeEventTypesData = collect([]);
            $lifeEventTypes = $lifeEventCategory->lifeEventTypes;

            foreach ($lifeEventTypes as $lifeEventType) {
                $dataLifeEventType = [
                    'id' => $lifeEventType->id,
                    'name' => $lifeEventType->name,
                    'default_life_event_type_key' => $lifeEventType->default_life_event_type_key,
                ];
                $lifeEventTypesData->push($dataLifeEventType);
            }

            $data = [
                'id' => $lifeEventCategory->id,
                'name' => $lifeEventCategory->name,
                'default_life_event_category_key' => $lifeEventCategory->default_life_event_category_key,
                'lifeEventTypes' => $lifeEventTypesData,
            ];
            $lifeEventCategoriesData->push($data);
        }

        return $lifeEventCategoriesData;
    }
}
