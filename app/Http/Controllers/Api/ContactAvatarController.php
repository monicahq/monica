<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DestroyContactAvatar;
use App\Actions\UpdateContactAvatar;
use App\Exceptions\NotEnoughStorageLeft;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The picture of a contact, which is not a field: it is what the product asks
 * about somebody whatever template they are on.
 *
 * The upload arrives as multipart form data on a POST rather than a PUT, because
 * PHP fills in an uploaded file on a POST and on nothing else. That is why the
 * browser path posts it too.
 *
 * The picture becomes a file of the vault, so it counts against the account's
 * storage, and an upload that would take the account past it is refused as
 * invalid rather than failing. Taking a picture off lets the file behind it go
 * rather than deleting it, because a vault's files outlive what pointed at them.
 */
class ContactAvatarController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->where('uuid', $vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->where('uuid', $contact)->firstOrFail();

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        try {
            new UpdateContactAvatar(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                upload: $validated['avatar'],
            )->execute();
        } catch (NotEnoughStorageLeft $e) {
            throw ValidationException::withMessages(['avatar' => $e->getMessage()]);
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'templateVersion.template', 'avatar']),
        )->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->where('uuid', $vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->where('uuid', $contact)->firstOrFail();

        new DestroyContactAvatar(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
        )->execute();

        return new ContactResource(
            $contact->refresh()->load(['vault', 'templateVersion.template', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
