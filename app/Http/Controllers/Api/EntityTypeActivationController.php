<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateEntityType;
use App\Actions\DeactivateEntityType;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityTypeResource;
use App\Models\EntityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Whether records may still be filed under a kind of object.
 *
 * This is its own address rather than a field of the type, because it is a
 * decision about the account rather than a correction to the wording, and
 * because it is what a type in use gets instead of a deletion. Folding it into
 * the update would let a rename typed in a script silently stop an account
 * filing records, and would write two entries to the journal under one name.
 *
 * Switching a type off touches nothing already filed under it: every record
 * stays readable and stays editable, and the place the type has in the
 * navigation is kept, so switching it back on puts it where it was.
 *
 * The state wanted is sent rather than a toggle, so that a request lost on the
 * way and sent again lands where the caller meant rather than inverting.
 */
class EntityTypeActivationController extends Controller
{
    public function update(Request $request, string $entityType): JsonResponse
    {
        $entityType = $request->user()->account->entityTypes()
            ->where('uuid', $entityType)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $entityType);

        return new EntityTypeResource($entityType->refresh())->response()->setStatusCode(200);
    }

    /**
     * Each state through the operation that owns it, so that each is written to
     * the journal on its own, exactly as the settings screen does it.
     *
     * A request asking for the state the type is already in changes nothing and
     * says so by answering with the type, rather than reporting a change nobody
     * made.
     */
    private function settle(Request $request, EntityType $entityType): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $entityType->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateEntityType(user: $request->user(), entityType: $entityType)->execute();

            return;
        }

        new DeactivateEntityType(user: $request->user(), entityType: $entityType)->execute();
    }
}
