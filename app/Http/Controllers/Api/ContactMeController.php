<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMeController extends Controller
{
    public function show(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();

        $contact = Contact::query()
            ->where('vault_id', $vault->id)
            ->where('is_me', true)
            ->with(['vault', 'structureVersion.structure.owner', 'avatar'])
            ->firstOrFail();

        return new ContactResource($contact)->response()->setStatusCode(200);
    }
}
