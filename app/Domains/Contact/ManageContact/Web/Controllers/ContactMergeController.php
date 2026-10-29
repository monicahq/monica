<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\MergeContacts;
use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactShowMergeViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Redirect;

class ContactMergeController extends Controller
{
    public function show(Request $request, string $vaultId, string $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/Merge', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowMergeViewHelper::data($contact, Auth::user(), $vault),
        ]);
    }

    public function store(Request $request, string $vaultId, string $contactId)
    {
        Gate::authorize('vault-editor', $vaultId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'primary_contact_id' => $contactId,
            'duplicate_contact_id' => $request->input('duplicate_contact_id'),
        ];

        (new MergeContacts)->execute($data);

        return Redirect::route('contact.show', [
            'vault' => $vaultId,
            'contact' => $contactId,
        ]);
    }
}
