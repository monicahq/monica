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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactAvatarController extends Controller
{
    public function show(Request $request, string $vault, string $contact): StreamedResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->with('avatar')->firstOrFail();

        abort_if($contact->avatar === null, 404);

        $disk = $contact->avatar->disk();
        $path = $contact->avatar->variantPath(Contact::AVATAR_THUMBNAIL_PIXELS) ?? $contact->avatar->path;

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, name: $contact->avatar->original_name, headers: [
            'Content-Type' => $contact->avatar->mime_type,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ], disposition: $contact->avatar->contentDisposition());
    }

    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120', 'dimensions:max_width=8192,max_height=8192'],
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
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar']),
        )->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        new DestroyContactAvatar(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
        )->execute();

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
