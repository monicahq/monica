<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\EndMobileSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\MobileSessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MobileSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $sessions = $request->user()->mobileSessions()
            ->active()
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => MobileSessionResource::collection($sessions->items())->resolve(),
            'meta' => [
                'page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function show(Request $request, int $mobileSession): JsonResponse
    {
        $session = $request->user()->mobileSessions()->active()->findOrFail($mobileSession);

        return new MobileSessionResource($session)->response()->setStatusCode(200);
    }

    public function destroy(Request $request, int $mobileSession): Response
    {
        new EndMobileSession(
            user: $request->user(),
            session: $request->user()->mobileSessions()->active()->findOrFail($mobileSession),
        )->execute();

        return response()->noContent(204);
    }
}
