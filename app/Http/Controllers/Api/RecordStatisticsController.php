<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\RecordKind;
use App\Helpers\RecordStatistics;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecordStatisticsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordStatisticsController extends Controller
{
    public function show(Request $request, string $vault, string $recordKind): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $kind = RecordKind::fromApiSegment(vault: $vault, segment: $recordKind);

        return new RecordStatisticsResource([
            'kind' => $kind,
            'statistics' => new RecordStatistics(kind: $kind)->toArray(),
        ])->response()->setStatusCode(200);
    }
}
