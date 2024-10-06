<?php

namespace App\Domains\Contact\ManageRelationships\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Domains\Contact\ManageRelationships\Services\SetRelationship;
use App\Domains\Contact\ManageRelationships\Services\UnsetRelationship;
use App\Domains\Contact\ManageRelationships\Web\ViewHelpers\ContactRelationshipsCreateViewHelper;
use App\Domains\Contact\ManageRelationships\Web\ViewHelpers\ModuleRelationshipViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ContactRelationshipsController extends Controller
{
    public function create(Request $request, string $vaultId, string $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/Relationships/Create', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactRelationshipsCreateViewHelper::data($vault, $contact, Auth::user()),
        ]);
    }

    public function store(Request $request, string $vaultId, string $contactId)
    {
        // This is a complex controller method, sorry about that, future reader
        // It's complex because the form is really complex and can lead to
        // many different scenarios

        // A relationship is defined by a contact <-> other contact
        // or "from" => "to".
        // One of the contact is necessary the contact that we were looking at.
        // This contact is $contactId.
        // The other contact is "otherContactId".
        // To know which contact is the "from" contact, we pass it as a parameter.

        // first, let's create a contact if there is no contact selected
        $otherContactId = 0;
        if ($request->input('choice') !== 'contact') {
            $otherContact = (new CreateContact)->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
                'vault_id' => $vaultId,
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'middle_name' => $request->input('middle_name'),
                'nickname' => $request->input('nickname'),
                'maiden_name' => $request->input('maiden_name'),
                'gender_id' => $request->input('gender_id'),
                'pronoun_id' => $request->input('pronoun_id'),
                'listed' => $request->input('create_contact_entry'),
                'template_id' => null,
            ]);
            $otherContactId = $otherContact->id;
        } else {
            $otherContactId = collect($request->input('other_contact_id'))->pluck('id')->first();
        }

        (new SetRelationship)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'relationship_type_id' => $request->input('relationship_type_id'),
            'contact_id' => $request->input('base_contact_id') === $contactId ? $contactId : $otherContactId,
            'other_contact_id' => $request->input('base_contact_id') === $contactId ? $otherContactId : $contactId,
        ]);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $relationshipId)
    {
        $relationship = DB::table('relationships')->where('id', $relationshipId)->first();

        (new UnsetRelationship)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'relationship_type_id' => $relationship->relationship_type_id,
            'contact_id' => $relationship->contact_id,
            'other_contact_id' => $relationship->related_contact_id,
        ]);

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'data' => ModuleRelationshipViewHelper::data($contact, Auth::user()),
        ], 200);
    }
}
