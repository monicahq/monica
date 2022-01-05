<?php

namespace App\Http\Controllers\Settings\Personalize\ContactInformationTypes;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Services\Account\ManageContactInformationTypes\CreateContactInformationType;
use App\Services\Account\ManageContactInformationTypes\UpdateContactInformationType;
use App\Services\Account\ManageContactInformationTypes\DestroyContactInformationType;
use App\Http\Controllers\Settings\Personalize\ContactInformationTypes\ViewHelpers\PersonalizeContactInformationTypeIndexViewHelper;

class PersonalizeContatInformationTypesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/ContactInformationTypes/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeContactInformationTypeIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name' => $request->input('name'),
            'protocol' => $request->input('protocol'),
        ];

        $contactInformationType = (new CreateContactInformationType)->execute($data);

        return response()->json([
            'data' => PersonalizeContactInformationTypeIndexViewHelper::dtoContactInformationType($contactInformationType),
        ], 201);
    }

    public function update(Request $request, int $contactInformationTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'contact_information_type_id' => $contactInformationTypeId,
            'name' => $request->input('name'),
            'protocol' => $request->input('protocol'),
        ];

        $contactInformationType = (new UpdateContactInformationType)->execute($data);

        return response()->json([
            'data' => PersonalizeContactInformationTypeIndexViewHelper::dtoContactInformationType($contactInformationType),
        ], 200);
    }

    public function destroy(Request $request, int $contactInformationTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'contact_information_type_id' => $contactInformationTypeId,
        ];

        (new DestroyContactInformationType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
