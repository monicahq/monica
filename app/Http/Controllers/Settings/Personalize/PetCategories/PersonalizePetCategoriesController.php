<?php

namespace App\Http\Controllers\Settings\Personalize\PetCategories;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Account\ManagePetCategories\CreatePetCategory;
use App\Services\Account\ManagePetCategories\UpdatePetCategory;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Services\Account\ManagePetCategories\DestroyPetCategory;
use App\Http\Controllers\Settings\Personalize\PetCategories\ViewHelpers\PersonalizePetCategoriesIndexViewHelper;

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

        $petCategory = (new CreatePetCategory)->execute($data);

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

        $petCategory = (new UpdatePetCategory)->execute($data);

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

        (new DestroyPetCategory)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
