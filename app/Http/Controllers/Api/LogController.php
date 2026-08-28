<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What has been done in an account, newest first.
 *
 * The journal belongs to the account rather than to the person, which is the
 * same choice the settings screen makes and for the same reason: an entry the
 * application wrote on its own has nobody behind it, and scoping to the user
 * would hide exactly the entries nobody saw happen.
 *
 * There is nothing here but reading. A journal that can be rewritten is worth
 * nothing, the model refuses an update or a delete outright, and so this
 * controller offers neither.
 */
class LogController extends Controller
{
    /**
     * The page is shaped by hand for the same reason as everywhere else in this
     * API: the reference promises `page`, `per_page` and `total`, and nothing
     * more.
     */
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
