<?php

namespace App\Settings\ManageGroupTypes\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageGroupTypes\Services\UpdateGroupTypeRolePosition;
use App\Settings\ManageGroupTypes\Web\ViewHelpers\PersonalizeGroupTypeViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeGroupTypeRolePositionController extends Controller
{
    public function update(Request $request, int $groupTypeId, int $groupTypeRoleId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'group_type_id' => $groupTypeId,
            'group_type_role_id' => $groupTypeRoleId,
            'new_position' => $request->input('position'),
        ];

        $groupTypeRole = (new UpdateGroupTypeRolePosition())->execute($data);

        return response()->json([
            'data' => PersonalizeGroupTypeViewHelper::dto($groupTypeRole->groupType),
        ], 200);
    }
}
