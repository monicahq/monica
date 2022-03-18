<?php

namespace App\Http\Controllers\Vault\Contact;

use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use App\Models\TemplatePage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Vault\Contact\ViewHelpers\ContactShowViewHelper;

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

        return Inertia::render('Vault/Contact/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactShowViewHelper::dataForTemplatePage($contact, Auth::user(), $templatePage),
        ]);
    }
}
