<?php

namespace App\Contact\ManageContact\Web\Controllers;

use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use App\Models\TemplatePage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Contact\ManageContact\Services\UpdateContactView;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Contact\ManageContact\Web\ViewHelpers\ContactShowViewHelper;

class ContactPageController extends Controller
{
    public function show(Request $request, int $vaultId, int $contactId, string $slug)
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

        $templatePage = TemplatePage::where('slug', $slug)
            ->where('template_id', $contact->template_id)
            ->firstOrFail();

        (new UpdateContactView)->execute([
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::user()->id,
            'contact_id' => $contactId,
        ]);

        return Inertia::render('Vault/Contact/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowViewHelper::dataForTemplatePage($contact, Auth::user(), $templatePage),
        ]);
    }
}
