<?php

namespace App\Domains\Settings\ManageRelationshipTypes\Web\Controllers;

use App\Domains\Settings\ManageRelationshipTypes\Services\CreateRelationshipType;
use App\Domains\Settings\ManageRelationshipTypes\Services\DestroyRelationshipType;
use App\Domains\Settings\ManageRelationshipTypes\Services\UpdateRelationshipType;
use App\Domains\Settings\ManageRelationshipTypes\Web\ViewHelpers\PersonalizeRelationshipIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizeRelationshipTypeController extends Controller
{
    public function store(Request $request, int $groupTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'relationship_group_type_id' => $groupTypeId,
            'name' => $request->input('name'),
            'name_reverse_relationship' => $request->input('nameReverseRelationship'),
            'can_be_deleted' => true,
        ];

        $type = (new CreateRelationshipType)->execute($data);

        return response()->json([
            'data' => PersonalizeRelationshipIndexViewHelper::dtoRelationshipType($type->groupType, $type),
        ], 201);
    }

    public function update(Request $request, int $groupTypeId, int $typeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'relationship_group_type_id' => $groupTypeId,
            'relationship_type_id' => $typeId,
            'name' => $request->input('name'),
            'name_reverse_relationship' => $request->input('nameReverseRelationship'),
        ];

        $type = (new UpdateRelationshipType)->execute($data);

        return response()->json([
            'data' => PersonalizeRelationshipIndexViewHelper::dtoRelationshipType($type->groupType, $type),
        ], 200);
    }

    public function destroy(Request $request, int $groupTypeId, int $typeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'relationship_group_type_id' => $groupTypeId,
            'relationship_type_id' => $typeId,
        ];

        (new DestroyRelationshipType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
