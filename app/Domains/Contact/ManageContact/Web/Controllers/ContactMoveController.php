<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\MoveContactToAnotherVault;
use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactShowMoveViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Redirect;

class ContactMoveController extends Controller
{
    public function show(Request $request, string $vaultId, string $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/Move', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowMoveViewHelper::data($contact, Auth::user()),
        ]);
    }

    public function store(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'other_vault_id' => $request->input('other_vault_id'),
            'contact_id' => $contactId,
        ];

        (new MoveContactToAnotherVault)->execute($data);

        return Redirect::route('contact.show', [
            'vault' => $request->input('other_vault_id'),
            'contact' => $contactId,
        ]);
    }
}
