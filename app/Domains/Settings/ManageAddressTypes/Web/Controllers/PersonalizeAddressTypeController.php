<?php

namespace App\Domains\Settings\ManageAddressTypes\Web\Controllers;

use App\Domains\Settings\ManageAddressTypes\Services\CreateAddressType;
use App\Domains\Settings\ManageAddressTypes\Services\DestroyAddressType;
use App\Domains\Settings\ManageAddressTypes\Services\UpdateAddressType;
use App\Domains\Settings\ManageAddressTypes\Web\ViewHelpers\PersonalizeAddressTypeIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
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
            'author_id' => Auth::id(),
            'name' => $request->input('name'),
            'type' => $request->input('type'),
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
            'author_id' => Auth::id(),
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
            'author_id' => Auth::id(),
            'address_type_id' => $addressTypeId,
        ];

        (new DestroyAddressType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
