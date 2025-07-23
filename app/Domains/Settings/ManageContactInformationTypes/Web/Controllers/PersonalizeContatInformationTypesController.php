<?php

namespace App\Domains\Settings\ManageContactInformationTypes\Web\Controllers;

use App\Domains\Settings\ManageContactInformationTypes\Services\CreateContactInformationType;
use App\Domains\Settings\ManageContactInformationTypes\Services\DestroyContactInformationType;
use App\Domains\Settings\ManageContactInformationTypes\Services\UpdateContactInformationType;
use App\Domains\Settings\ManageContactInformationTypes\Web\ViewHelpers\PersonalizeContactInformationTypeIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
            'author_id' => Auth::id(),
            'name' => $request->input('name'),
            'type' => $request->input('type'),
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
            'author_id' => Auth::id(),
            'contact_information_type_id' => $contactInformationTypeId,
            'name' => $request->input('name'),
            'type' => $request->input('type'),
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
            'author_id' => Auth::id(),
            'contact_information_type_id' => $contactInformationTypeId,
        ];

        (new DestroyContactInformationType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
