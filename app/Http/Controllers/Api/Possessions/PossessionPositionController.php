<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\UpdatePossessionPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionResource;
use App\Models\Possession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PossessionPositionController extends Controller
{
    public function update(Request $request, string $vault, string $possession): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $record = Possession::query()->where('vault_id', $vault->id)->where('uuid', $possession)->firstOrFail();
        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdatePossessionPosition(
            user: $request->user(),
            possession: $record,
            position: (int) $validated['position'],
        )->execute();

        return new PossessionResource($record->refresh())->response();
    }
}
