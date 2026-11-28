<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\EndOAuthConnection;
use App\Http\Controllers\Controller;
use App\Http\Resources\OAuthConnectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OAuthConnectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $connections = $request->user()->oauthConnections()
            ->with('client')
            ->active()
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => OAuthConnectionResource::collection($connections->items())->resolve(),
            'meta' => [
                'page' => $connections->currentPage(),
                'per_page' => $connections->perPage(),
                'total' => $connections->total(),
            ],
        ]);
    }

    public function show(Request $request, int $oauthConnection): JsonResponse
    {
        $connection = $request->user()->oauthConnections()->active()->findOrFail($oauthConnection);

        return new OAuthConnectionResource($connection)->response()->setStatusCode(200);
    }

    public function destroy(Request $request, int $oauthConnection): Response
    {
        new EndOAuthConnection(
            user: $request->user(),
            connection: $request->user()->oauthConnections()->active()->findOrFail($oauthConnection),
        )->execute();

        return response()->noContent(204);
    }
}
