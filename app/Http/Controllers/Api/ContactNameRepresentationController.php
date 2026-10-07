<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateContactNameRepresentations;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactNameRepresentationResource;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ContactNameRepresentationController extends Controller
{
    public function index(Request $request, string $vault, string $contact): JsonResponse
    {
        $contact = $this->contact($request, $vault, $contact);

        return response()->json([
            'data' => ContactNameRepresentationResource::collection($contact->nameRepresentations)->resolve($request),
        ]);
    }

    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $contact = $this->contact($request, $vault, $contact);

        $validated = $request->validate([
            ...UpdateContactNameRepresentations::rules('name_representations', $contact->nameRepresentations()->pluck('uuid')->all()),
            'maiden_name' => ['present', 'nullable', 'string', 'max:255'],
            'nickname' => ['present', 'nullable', 'string', 'max:255'],
        ]);

        try {
            new UpdateContactNameRepresentations(
                user: $request->user(),
                contact: $contact,
                nameRepresentations: array_values($validated['name_representations']),
                display: isset($validated['display']) ? (int) $validated['display'] : null,
                nickname: $validated['nickname'],
                maidenName: $validated['maiden_name'],
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['name_representations' => $e->getMessage()]);
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar', 'nameRepresentations']),
        )->response()->setStatusCode(200);
    }

    private function contact(Request $request, string $vault, string $contact): Contact
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();

        return Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->with('nameRepresentations')->firstOrFail();
    }
}
