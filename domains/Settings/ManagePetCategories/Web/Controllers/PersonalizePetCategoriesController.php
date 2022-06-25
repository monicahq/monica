<?php

namespace App\Settings\ManagePetCategories\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManagePetCategories\Services\CreatePetCategory;
use App\Settings\ManagePetCategories\Services\DestroyPetCategory;
use App\Settings\ManagePetCategories\Services\UpdatePetCategory;
use App\Settings\ManagePetCategories\Web\ViewHelpers\PersonalizePetCategoriesIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizePetCategoriesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/PetCategories/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizePetCategoriesIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name' => $request->input('name'),
        ];

        $petCategory = (new CreatePetCategory())->execute($data);

        return response()->json([
            'data' => PersonalizePetCategoriesIndexViewHelper::dtoPetCategory($petCategory),
        ], 201);
    }

    public function update(Request $request, int $petCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'pet_category_id' => $petCategoryId,
            'name' => $request->input('name'),
        ];

        $petCategory = (new UpdatePetCategory())->execute($data);

        return response()->json([
            'data' => PersonalizePetCategoriesIndexViewHelper::dtoPetCategory($petCategory),
        ], 200);
    }

    public function destroy(Request $request, int $petCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'pet_category_id' => $petCategoryId,
        ];

        (new DestroyPetCategory())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
