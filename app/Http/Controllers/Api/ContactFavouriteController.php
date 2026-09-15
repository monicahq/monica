<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\FavouriteContact;
use App\Actions\UnfavouriteContact;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactFavouriteController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $request->validate(['favourite' => ['required', 'boolean']]);

        if ($request->boolean('favourite')) {
            new FavouriteContact(user: $request->user(), contact: $contact)->execute();
        }

        if (! $request->boolean('favourite')) {
            new UnfavouriteContact(user: $request->user(), contact: $contact)->execute();
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
