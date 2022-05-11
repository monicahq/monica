<?php

namespace App\Contact\ManageContact\Web\Controllers;

use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Contact\ManageContact\Services\CreateContact;
use App\Contact\ManageContact\Services\UpdateContact;
use App\Contact\ManageContact\Services\DestroyContact;
use App\Contact\ManageContact\Services\UpdateContactView;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Contact\ManageContact\Web\ViewHelpers\ContactEditViewHelper;
use App\Contact\ManageContact\Web\ViewHelpers\ContactShowViewHelper;
use App\Contact\ManageContact\Web\ViewHelpers\ContactIndexViewHelper;
use App\Contact\ManageContact\Web\ViewHelpers\ContactCreateViewHelper;

class ContactController extends Controller
{
    public function index(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        $contacts = Contact::where('vault_id', $request->route()->parameter('vault'))
            ->where('listed', true)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return Inertia::render('Vault/Contact/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactIndexViewHelper::data($contacts, Auth::user(), $vault),
        ]);
    }

    public function create(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Contact/Create', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactCreateViewHelper::data($vault),
        ]);
    }

    public function store(Request $request, int $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'middle_name' => $request->input('middle_name'),
            'nickname' => $request->input('nickname'),
            'maiden_name' => $request->input('maiden_name'),
            'gender_id' => $request->input('gender_id'),
            'pronoun_id' => $request->input('pronoun_id'),
            'template_id' => $request->input('template_id'),
            'listed' => true,
        ];

        $contact = (new CreateContact)->execute($data);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contact,
            ]),
        ], 201);
    }

    public function show(Request $request, int $vaultId, int $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::with('gender')
            ->with('pronoun')
            ->with('notes')
            ->with('dates')
            ->with('vault')
            ->findOrFail($contactId);

        if (! $contact->template_id) {
            return redirect()->route('contact.blank', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]);
        }

        (new UpdateContactView)->execute([
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::user()->id,
            'contact_id' => $contactId,
        ]);

        return Inertia::render('Vault/Contact/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowViewHelper::data($contact, Auth::user()),
        ]);
    }

    public function edit(Request $request, int $vaultId, int $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/Names/Edit', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactEditViewHelper::data($vault, $contact, Auth::user()),
        ]);
    }

    public function update(Request $request, int $vaultId, int $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'middle_name' => $request->input('middle_name'),
            'nickname' => $request->input('nickname'),
            'maiden_name' => $request->input('maiden_name'),
            'gender_id' => $request->input('gender_id'),
            'pronoun_id' => $request->input('pronoun_id'),
        ];

        $contact = (new UpdateContact)->execute($data);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contact,
            ]),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ];

        (new DestroyContact)->execute($data);

        return response()->json([
            'data' => route('contact.index', [
                'vault' => $vaultId,
            ]),
        ], 200);
    }
}
