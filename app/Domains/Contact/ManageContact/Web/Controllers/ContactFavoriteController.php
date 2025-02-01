<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\ToggleFavoriteContact;
use App\Domains\Contact\ManageContactName\Web\ViewHelpers\ModuleContactNameViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactFavoriteController extends Controller
{
    public function update(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ];

        (new ToggleFavoriteContact)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactNameViewHelper::data($contact, Auth::user()),
        ], 200);
    }
}
