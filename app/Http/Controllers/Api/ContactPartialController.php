<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DemoteFullContact;
use App\Actions\PromotePartialContact;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ContactPartialController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $request->validate(['partial' => ['required', 'boolean']]);

        try {
            if ($request->boolean('partial')) {
                new DemoteFullContact(user: $request->user(), contact: $contact)->execute();
            }

            if (! $request->boolean('partial')) {
                new PromotePartialContact(user: $request->user(), contact: $contact)->execute();
            }
        } catch (AuthorizationException|InvalidArgumentException $e) {
            throw ValidationException::withMessages(['partial' => $e->getMessage()]);
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
