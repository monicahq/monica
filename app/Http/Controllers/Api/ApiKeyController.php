<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateApiKey;
use App\Actions\DestroyApiKey;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiKeyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The keys somebody has minted, which is how a key is rotated without opening
 * the application.
 *
 * A key acts as one person, so a caller reaches their own and nobody else's.
 * Asking for somebody else's by its identifier finds nothing rather than
 * refusing, which is the same answer a key that never existed gets.
 *
 * Minting one here is deliberate rather than an oversight. There is no stronger
 * credential to demand, and every creation and revocation is written to the
 * journal, so a key that mints other keys is visible afterwards.
 */
class ApiKeyController extends Controller
{
    /**
     * The page is shaped by hand rather than left to the framework, because the
     * reference promises `page`, `per_page` and `total` and nothing else, and a
     * promise made to a caller before the first list existed is still a promise.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $apiKeys = $request->user()->tokens()
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => ApiKeyResource::collection($apiKeys->items())->resolve(),
            'meta' => [
                'page' => $apiKeys->currentPage(),
                'per_page' => $apiKeys->perPage(),
                'total' => $apiKeys->total(),
            ],
        ]);
    }

    public function show(Request $request, int $apiKey): JsonResponse
    {
        $key = $request->user()->tokens()->findOrFail($apiKey);

        return new ApiKeyResource($key)->response()->setStatusCode(200);
    }

    /**
     * The readable key rides beside the envelope rather than inside it, so that
     * the shape describing a key stays one that can never carry a secret.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $key = new CreateApiKey(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return new ApiKeyResource($key->accessToken)
            ->additional(['token' => $key->plainTextToken])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, int $apiKey): Response
    {
        new DestroyApiKey(
            user: $request->user(),
            apiKeyId: $apiKey,
        )->execute();

        return response()->noContent(204);
    }
}
