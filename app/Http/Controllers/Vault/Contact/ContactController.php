<?php

namespace App\Http\Controllers\Vault\Contact;

use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Contact\ManageContact\CreateContact;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Vault\Contact\ViewHelpers\ContactShowViewHelper;
use App\Http\Controllers\Vault\Contact\ViewHelpers\ContactIndexViewHelper;
use App\Http\Controllers\Vault\Contact\ViewHelpers\ContactCreateViewHelper;

class ContactController extends Controller
{
    public function index(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        $contacts = Contact::where('vault_id', $request->route()->parameter('vault'))
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
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowViewHelper::data($contact),
        ]);
    }
}
