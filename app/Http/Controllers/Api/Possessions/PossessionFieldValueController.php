<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\UpdatePossessionFieldValues;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionResource;
use App\Models\Possession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PossessionFieldValueController extends Controller
{
    public function update(Request $request, string $vault, string $possession): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $record = Possession::query()->where('vault_id', $vault->id)->where('uuid', $possession)->firstOrFail();
        $validated = $request->validate([
            'values' => ['present', 'array'],
        ]);

        new UpdatePossessionFieldValues(
            user: $request->user(),
            possession: $record,
            values: $validated['values'],
        )->execute();

        return new PossessionResource($record->refresh())->response();
    }
}
