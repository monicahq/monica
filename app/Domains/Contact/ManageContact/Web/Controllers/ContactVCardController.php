<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\Dav\Services\ExportVCard;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactVCardController extends Controller
{
    public function download(Request $request, Vault $vault, Contact $contact)
    {
        $carddata = $this->exportVCard($vault->id, $contact->id);

        return response()->streamDownload(function () use ($carddata) {
            echo $carddata;
        }, $contact->id.'.vcf', [
            'Content-Type' => 'text/vcard',
        ], 'inline');
    }

    /**
     * Get the new exported version of the object.
     */
    protected function exportVCard(string $vault_id, string $contact_id): string
    {
        $vcard = app(ExportVCard::class)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault_id,
            'contact_id' => $contact_id,
        ]);

        return $vcard->serialize();
    }
}
