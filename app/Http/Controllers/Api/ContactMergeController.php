<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MergeContacts;
use App\Actions\PreviewContactMerge;
use App\Actions\UndoContactMerge;
use App\Exceptions\CannotAbsorbTheMeContact;
use App\Exceptions\CannotMergeAContactWithItself;
use App\Exceptions\ContactChangedSinceThePreview;
use App\Exceptions\ContactMergeCannotBeUndone;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMergePreviewResource;
use App\Http\Resources\ContactMergeResource;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ContactMergeController extends Controller
{
    public function show(Request $request, string $vault, string $contact, string $other): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        try {
            $preview = new PreviewContactMerge(
                user: $request->user(),
                kept: $this->contact($vault, $contact),
                absorbed: $this->contact($vault, $other),
            )->execute();
        } catch (CannotAbsorbTheMeContact|CannotMergeAContactWithItself $e) {
            throw ValidationException::withMessages(['other' => $e->getMessage()]);
        }

        return response()->json(['data' => new ContactMergePreviewResource($preview)]);
    }

    public function create(Request $request, string $vault, string $contact, string $other): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'fingerprint' => ['nullable', 'string'],
            'resolutions' => ['array'],
            'resolutions.*.field_key' => ['required', 'string'],
            'resolutions.*.row' => ['nullable', 'integer'],
            'resolutions.*.keep' => ['required', 'string', 'in:kept,other,both'],
        ]);

        try {
            $merge = new MergeContacts(
                user: $request->user(),
                kept: $this->contact($vault, $contact),
                absorbed: $this->contact($vault, $other),
                resolutions: array_values($validated['resolutions'] ?? []),
                fingerprint: $validated['fingerprint'] ?? null,
            )->execute();
        } catch (ContactChangedSinceThePreview $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (CannotAbsorbTheMeContact|CannotMergeAContactWithItself $e) {
            throw ValidationException::withMessages(['other' => $e->getMessage()]);
        }

        return response()->json(['data' => new ContactMergeResource($merge)], 201);
    }

    public function destroy(Request $request, string $vault, string $merge): Response
    {
        $vault = $this->vault($request, $vault);

        try {
            new UndoContactMerge(
                user: $request->user(),
                merge: ContactMerge::query()
                    ->where('vault_id', $vault->id)
                    ->where('uuid', $merge)
                    ->firstOrFail(),
            )->execute();
        } catch (ContactMergeCannotBeUndone $e) {
            throw ValidationException::withMessages(['merge' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function vault(Request $request, string $uuid): Vault
    {
        return $request->user()->account->vaults()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function contact(Vault $vault, string $uuid): Contact
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
