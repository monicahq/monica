<?php

namespace App\Domains\Contact\ManageReligion\Web\Controllers;

use App\Domains\Contact\ManageReligion\Services\UpdateReligion;
use App\Domains\Contact\ManageReligion\Web\ViewHelpers\ModuleReligionViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleReligionController extends Controller
{
    public function update(Request $request, string $vaultId, string $contactId)
    {
        (new UpdateReligion)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'religion_id' => $request->input('religion_id'),
        ]);

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'data' => ModuleReligionViewHelper::data($contact),
        ], 200);
    }
}
