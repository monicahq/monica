<?php

namespace App\Domains\Settings\ManageGroupTypes\Web\Controllers;

use App\Domains\Settings\ManageGroupTypes\Services\CreateGroupType;
use App\Domains\Settings\ManageGroupTypes\Services\DestroyGroupType;
use App\Domains\Settings\ManageGroupTypes\Services\UpdateGroupType;
use App\Domains\Settings\ManageGroupTypes\Web\ViewHelpers\PersonalizeGroupTypeViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeGroupTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/GroupTypes/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeGroupTypeViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'label' => $request->input('label'),
        ];

        $groupType = (new CreateGroupType)->execute($data);

        return response()->json([
            'data' => PersonalizeGroupTypeViewHelper::dto($groupType),
        ], 201);
    }

    public function update(Request $request, int $groupTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'group_type_id' => $groupTypeId,
            'label' => $request->input('label'),
        ];

        $groupType = (new UpdateGroupType)->execute($data);

        return response()->json([
            'data' => PersonalizeGroupTypeViewHelper::dto($groupType),
        ], 200);
    }

    public function destroy(Request $request, int $groupTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'group_type_id' => $groupTypeId,
        ];

        (new DestroyGroupType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
