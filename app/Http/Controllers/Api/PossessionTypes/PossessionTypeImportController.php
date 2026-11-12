<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTypes;

use App\Actions\ImportPossessionType;
use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Helpers\PossessionTypeFileReader;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PossessionTypeImportController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.PossessionTypeFileReader::MAX_BYTES],
        ]);

        try {
            $possessionType = new ImportPossessionType(
                user: $request->user(),
                raw: $validated['file'],
            )->execute();
        } catch (PossessionTypeFileIsNotReadable $exception) {
            throw ValidationException::withMessages(['file' => $exception->problems]);
        }

        return new PossessionTypeResource($possessionType)->response()->setStatusCode(201);
    }
}
