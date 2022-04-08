<?php

namespace App\Settings\ManageGenders\Web\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageGenders\Services\CreateGender;
use App\Settings\ManageGenders\Services\UpdateGender;
use App\Settings\ManageGenders\Services\DestroyGender;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Settings\ManageGenders\Web\ViewHelpers\ManageGenderIndexViewHelper;

class ManageGenderController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Genders/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => ManageGenderIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name' => $request->input('name'),
        ];

        $gender = (new CreateGender)->execute($data);

        return response()->json([
            'data' => ManageGenderIndexViewHelper::dtoGender($gender),
        ], 201);
    }

    public function update(Request $request, int $genderId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'gender_id' => $genderId,
            'name' => $request->input('name'),
        ];

        $gender = (new UpdateGender)->execute($data);

        return response()->json([
            'data' => ManageGenderIndexViewHelper::dtoGender($gender),
        ], 200);
    }

    public function destroy(Request $request, int $genderId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'gender_id' => $genderId,
        ];

        (new DestroyGender)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
