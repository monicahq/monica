<?php

namespace App\Domains\Settings\ManageLifeEventCategories\Web\Controllers;

use App\Domains\Settings\ManageLifeEventCategories\Services\CreateLifeEventCategory;
use App\Domains\Settings\ManageLifeEventCategories\Services\DestroyLifeEventCategory;
use App\Domains\Settings\ManageLifeEventCategories\Services\UpdateLifeEventCategory;
use App\Domains\Settings\ManageLifeEventCategories\Web\ViewHelpers\PersonalizeLifeEventCategoriesViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeLifeEventCategoriesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/LifeEventCategories/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeLifeEventCategoriesViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'label' => $request->input('lifeEventCategoryName'),
            'can_be_deleted' => true,
            'type' => null,
        ];

        $lifeEventCategory = (new CreateLifeEventCategory())->execute($data);

        return response()->json([
            'data' => PersonalizeLifeEventCategoriesViewHelper::dtoLifeEventCategory($lifeEventCategory),
        ], 201);
    }

    public function update(Request $request, int $lifeEventCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
            'label' => $request->input('lifeEventCategoryName'),
            'can_be_deleted' => $request->input('canBeDeleted'),
            'type' => $request->input('type'),
        ];

        $lifeEventCategory = (new UpdateLifeEventCategory())->execute($data);

        return response()->json([
            'data' => PersonalizeLifeEventCategoriesViewHelper::dtoLifeEventCategory($lifeEventCategory),
        ], 200);
    }

    public function destroy(Request $request, int $lifeEventCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
        ];

        (new DestroyLifeEventCategory())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
