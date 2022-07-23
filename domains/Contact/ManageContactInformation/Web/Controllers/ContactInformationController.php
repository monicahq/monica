<?php

namespace App\Contact\ManageContactInformation\Web\Controllers;

use App\Contact\ManageContactInformation\Services\CreateContactInformation;
use App\Contact\ManageContactInformation\Services\DestroyContactInformation;
use App\Contact\ManageContactInformation\Services\UpdateContactInformation;
use App\Contact\ManageContactInformation\Web\ViewHelpers\ModuleContactInformationViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactInformationController extends Controller
{
    public function store(Request $request, int $vaultId, int $contactId)
    {
        $info = (new CreateContactInformation())->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_information_type_id' => $request->input('contact_information_type_id'),
            'data' => $request->input('data'),
        ]);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactInformationViewHelper::dto($contact, $info),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $contactId, int $infoId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_information_id' => $infoId,
            'contact_information_type_id' => $request->input('contact_information_type_id'),
            'data' => $request->input('data'),
        ];

        $info = (new UpdateContactInformation())->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactInformationViewHelper::dto($contact, $info),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $contactId, int $infoId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_information_id' => $infoId,
        ];

        (new DestroyContactInformation())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
