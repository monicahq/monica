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

/**
 * Whether a contact sits at the top of the list.
 *
 * The request carries the state it wants rather than asking for a toggle. Two
 * callers acting on a stale reading would otherwise each invert what the other
 * had just written, and neither would end up with what it asked for. Sending the
 * state makes the request idempotent, so the worst a repeat can do is confirm
 * what is already stored.
 */
class ContactFavouriteController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->where('uuid', $vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->where('uuid', $contact)->firstOrFail();

        $request->validate(['favourite' => ['required', 'boolean']]);

        if ($request->boolean('favourite')) {
            new FavouriteContact(user: $request->user(), contact: $contact)->execute();
        }

        if (! $request->boolean('favourite')) {
            new UnfavouriteContact(user: $request->user(), contact: $contact)->execute();
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'templateVersion.template', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
