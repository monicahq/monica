<?php

namespace App\Contact\ManageContact\Web\Controllers;

use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Contact\ManageContact\Web\ViewHelpers\ContactShowBlankViewHelper;

class ContactNoTemplateController extends Controller
{
    public function show(Request $request, int $vaultId, int $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        return Inertia::render('Vault/Contact/Blank', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowBlankViewHelper::data($contact),
        ]);
    }
}
