<?php

namespace App\Settings\ManageAddressTypes\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageAddressTypes\Services\CreateAddressType;
use App\Settings\ManageAddressTypes\Services\UpdateAddressType;
use App\Settings\ManageAddressTypes\Web\ViewHelpers\PersonalizeAddressTypeIndexViewHelper;
use App\Settings\ManagePronouns\Services\DestroyPronoun;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeAddressTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/AddressTypes/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeAddressTypeIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name' => $request->input('name'),
        ];

        $addressType = (new CreateAddressType)->execute($data);

        return response()->json([
            'data' => PersonalizeAddressTypeIndexViewHelper::dtoAddressType($addressType),
        ], 201);
    }

    public function update(Request $request, int $addressTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'address_type_id' => $addressTypeId,
            'name' => $request->input('name'),
        ];

        $addressType = (new UpdateAddressType)->execute($data);

        return response()->json([
            'data' => PersonalizeAddressTypeIndexViewHelper::dtoAddressType($addressType),
        ], 200);
    }

    public function destroy(Request $request, int $addressTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'address_type_id' => $addressTypeId,
        ];

        (new DestroyPronoun)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
