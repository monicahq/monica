<?php

namespace App\Settings\ManageGroupTypes\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageGroupTypes\Services\UpdateGroupTypePosition;
use App\Settings\ManageGroupTypes\Web\ViewHelpers\PersonalizeGroupTypeViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeGroupTypePositionController extends Controller
{
    public function update(Request $request, int $groupTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'group_type_id' => $groupTypeId,
            'new_position' => $request->input('position'),
        ];

        $groupType = (new UpdateGroupTypePosition())->execute($data);

        return response()->json([
            'data' => PersonalizeGroupTypeViewHelper::dto($groupType),
        ], 200);
    }
}
