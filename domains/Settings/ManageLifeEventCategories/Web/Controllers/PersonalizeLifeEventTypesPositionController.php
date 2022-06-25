<?php

namespace App\Settings\ManageLifeEventCategories\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageLifeEventCategories\Services\UpdateLifeEventTypePosition;
use App\Settings\ManageLifeEventCategories\Web\ViewHelpers\PersonalizeLifeEventCategoriesViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeLifeEventTypesPositionController extends Controller
{
    public function update(Request $request, int $lifeEventCategoryId, int $lifeEventTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'life_event_category_id' => $lifeEventCategoryId,
            'life_event_type_id' => $lifeEventTypeId,
            'new_position' => $request->input('position'),
        ];

        $lifeEventType = (new UpdateLifeEventTypePosition())->execute($data);

        return response()->json([
            'data' => PersonalizeLifeEventCategoriesViewHelper::dtoType($lifeEventType->lifeEventCategory, $lifeEventType),
        ], 200);
    }
}
