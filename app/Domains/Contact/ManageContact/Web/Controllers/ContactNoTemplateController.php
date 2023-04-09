<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactShowBlankViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactNoTemplateController extends Controller
{
    public function show(Request $request, string $vaultId, string $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/Blank', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowBlankViewHelper::data($contact),
        ]);
    }
}
