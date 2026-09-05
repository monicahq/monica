<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $logs = $request->user()->account->logs()
            ->with('user')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => LogResource::collection($logs->items())->resolve(),
            'meta' => [
                'page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(Request $request, int $log): JsonResponse
    {
        $entry = $request->user()->account->logs()
            ->with('user')
            ->findOrFail($log);

        return new LogResource($entry)->response()->setStatusCode(200);
    }
}
