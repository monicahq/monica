<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateEntityTypePosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Where a kind of object sits in the vault navigation, so the ones somebody
 * opens daily are the ones they reach first.
 *
 * This is its own address for the same reason the activation is: it is a
 * decision about the account rather than a correction to the wording, and the
 * list endpoint answers in this order, so moving a type changes what every
 * caller reads next.
 *
 * The type is taken out and put back where it was asked for, and every type of
 * the account is then numbered from one again. A position past the end puts it
 * last. Types nobody may file records under are numbered along with the rest,
 * since one switched back on has to find the place it had.
 */
class EntityTypePositionController extends Controller
{
    public function update(Request $request, string $entityType): JsonResponse
    {
        $entityType = $request->user()->account->entityTypes()
            ->where('uuid', $entityType)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateEntityTypePosition(
            user: $request->user(),
            entityType: $entityType,
            position: $request->integer('position'),
        )->execute();

        return new EntityTypeResource($entityType->refresh())->response()->setStatusCode(200);
    }
}
