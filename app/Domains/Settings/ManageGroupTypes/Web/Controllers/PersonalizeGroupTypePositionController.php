<?php

namespace App\Domains\Settings\ManageGroupTypes\Web\Controllers;

use App\Domains\Settings\ManageGroupTypes\Services\UpdateGroupTypePosition;
use App\Domains\Settings\ManageGroupTypes\Web\ViewHelpers\PersonalizeGroupTypeViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeGroupTypePositionController extends Controller
{
    public function update(Request $request, int $groupTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'group_type_id' => $groupTypeId,
            'new_position' => $request->input('position'),
        ];

        $groupType = (new UpdateGroupTypePosition)->execute($data);

        return response()->json([
            'data' => PersonalizeGroupTypeViewHelper::dto($groupType),
        ], 200);
    }
}
