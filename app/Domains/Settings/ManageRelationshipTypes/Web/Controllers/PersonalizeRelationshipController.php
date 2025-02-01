<?php

namespace App\Domains\Settings\ManageRelationshipTypes\Web\Controllers;

use App\Domains\Settings\ManageRelationshipTypes\Services\CreateRelationshipGroupType;
use App\Domains\Settings\ManageRelationshipTypes\Services\DestroyRelationshipGroupType;
use App\Domains\Settings\ManageRelationshipTypes\Services\UpdateRelationshipGroupType;
use App\Domains\Settings\ManageRelationshipTypes\Web\ViewHelpers\PersonalizeRelationshipIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeRelationshipController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Relationships/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeRelationshipIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'name' => $request->input('relationshipGroupTypeName'),
            'can_be_deleted' => true,
        ];

        $groupType = (new CreateRelationshipGroupType)->execute($data);

        return response()->json([
            'data' => PersonalizeRelationshipIndexViewHelper::dtoGroupType($groupType),
        ], 201);
    }

    public function update(Request $request, int $groupTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'relationship_group_type_id' => $groupTypeId,
            'name' => $request->input('relationshipGroupTypeName'),
        ];

        $groupType = (new UpdateRelationshipGroupType)->execute($data);

        return response()->json([
            'data' => PersonalizeRelationshipIndexViewHelper::dtoGroupType($groupType),
        ], 200);
    }

    public function destroy(Request $request, int $groupTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'relationship_group_type_id' => $groupTypeId,
        ];

        (new DestroyRelationshipGroupType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
