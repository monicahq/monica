<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTypes;

use App\Actions\ExportPossessionType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PossessionTypeExportController extends Controller
{
    public function show(Request $request, string $possessionType): JsonResponse
    {
        $file = new ExportPossessionType(
            user: $request->user(),
            possessionType: $request->user()->account->possessionTypes()->where('uuid', $possessionType)->firstOrFail(),
        )->execute();

        return response()->json($file);
    }
}
