<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\ExportContactVCard;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactVCardController extends Controller
{
    public function show(Request $request, Vault $vault, Contact $contact): StreamedResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $card = new ExportContactVCard(
            user: $request->user(),
            contact: $contact,
            reachesSensitiveFields: true,
        )->execute();

        return response()->streamDownload(
            function () use ($card): void {
                echo $card;
            },
            $contact->slug.'.vcf',
            ['Content-Type' => 'text/vcard; charset=utf-8'],
        );
    }
}
