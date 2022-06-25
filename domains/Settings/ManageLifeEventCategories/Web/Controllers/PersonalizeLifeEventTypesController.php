<?php

namespace App\Settings\ManageLifeEventCategories\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageLifeEventCategories\Services\CreateLifeEventType;
use App\Settings\ManageLifeEventCategories\Services\DestroyLifeEventType;
use App\Settings\ManageLifeEventCategories\Services\UpdateLifeEventType;
use App\Settings\ManageLifeEventCategories\Web\ViewHelpers\PersonalizeLifeEventCategoriesViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeLifeEventTypesController extends Controller
{
    public function store(Request $request, int $lifeEventCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'life_event_category_id' => $lifeEventCategoryId,
            'label' => $request->input('label'),
            'can_be_deleted' => true,
            'type' => null,
        ];

        $lifeEventType = (new CreateLifeEventType())->execute($data);

        return response()->json([
            'data' => PersonalizeLifeEventCategoriesViewHelper::dtoType($lifeEventType->lifeEventCategory, $lifeEventType),
        ], 201);
    }

    public function update(Request $request, int $lifeEventCategoryId, int $lifeEventTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'life_event_category_id' => $lifeEventCategoryId,
            'life_event_type_id' => $lifeEventTypeId,
            'label' => $request->input('label'),
            'can_be_deleted' => $request->input('canBeDeleted'),
            'type' => $request->input('type'),
        ];

        $lifeEventType = (new UpdateLifeEventType())->execute($data);

        return response()->json([
            'data' => PersonalizeLifeEventCategoriesViewHelper::dtoType($lifeEventType->lifeEventCategory, $lifeEventType),
        ], 200);
    }

    public function destroy(Request $request, int $lifeEventCategoryId, int $lifeEventTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'life_event_category_id' => $lifeEventCategoryId,
            'life_event_type_id' => $lifeEventTypeId,
        ];

        (new DestroyLifeEventType())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
