<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\UpdateContactView;
use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\TemplatePage;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactPageController extends Controller
{
    public function show(Request $request, string $vaultId, string $contactId, string $slug)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::with([
            'gender',
            'pronoun',
            'notes',
            'importantDates',
            'vault',
        ])
            ->findOrFail($contactId);

        if (! $contact->template_id) {
            return redirect()->route('contact.blank', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]);
        }

        $templatePage = TemplatePage::where('slug', $slug)
            ->where('template_id', $contact->template_id)
            ->firstOrFail();

        (new UpdateContactView)->execute([
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'contact_id' => $contactId,
        ]);

        return Inertia::render('Vault/Contact/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowViewHelper::dataForTemplatePage($contact, Auth::user(), $templatePage),
        ]);
    }
}
