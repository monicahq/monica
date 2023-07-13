<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\Dav\Services\ExportVCard;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ContactVCardController extends Controller
{
    public function download(Request $request, Vault $vault, Contact $contact)
    {
        $cardData = $this->exportVCard($vault->id, $contact->id);
        $name = Str::of($contact->name)->slug(language: App::getLocale());

        return response()->streamDownload(function () use ($cardData) {
            echo $cardData;
        }, "$name.vcf", ['Content-Type' => 'text/vcard'], 'inline');
    }

    /**
     * Get the new exported version of the object.
     */
    protected function exportVCard(string $vaultId, string $contactId): string
    {
        $vcard = app(ExportVCard::class)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ]);

        return $vcard->serialize();
    }
}
