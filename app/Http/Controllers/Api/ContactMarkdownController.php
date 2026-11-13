<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ExportContactMarkdown;
use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactMarkdownController extends Controller
{
    public function show(Request $request, Vault $vault, Contact $contact): StreamedResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $document = new ExportContactMarkdown(
            user: $request->user(),
            contact: $contact,
            reachesSensitiveFields: $this->reachesSensitiveFields($request->user()),
        )->execute();

        return response()->streamDownload(
            function () use ($document): void {
                echo $document;
            },
            "{$contact->slug}.md",
            ['Content-Type' => 'text/markdown; charset=utf-8'],
        );
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }
}
