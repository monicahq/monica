<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\ExportContactMarkdown;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactMarkdownController extends Controller
{
    public function show(Request $request, Vault $vault, Contact $contact): StreamedResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $document = new ExportContactMarkdown(
            user: $request->user(),
            contact: $contact,
            reachesSensitiveFields: true,
        )->execute();

        return response()->streamDownload(
            function () use ($document): void {
                echo $document;
            },
            "{$contact->slug}.md",
            ['Content-Type' => 'text/markdown; charset=utf-8'],
        );
    }
}
