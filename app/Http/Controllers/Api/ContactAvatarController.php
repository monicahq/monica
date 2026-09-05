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
