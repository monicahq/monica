<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateApiKey;
use App\Actions\DestroyApiKey;
use App\Enums\ApiKeyClientEnum;
use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiKeyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
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

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'sensitive_fields' => ['sometimes', 'boolean'],
            'client' => ['sometimes', Rule::enum(ApiKeyClientEnum::class)],
        ]);

        $key = new CreateApiKey(
            user: $request->user(),
            name: $validated['name'],
            scopes: $this->scopes((bool) ($validated['sensitive_fields'] ?? false)),
            client: ApiKeyClientEnum::tryFrom((string) ($validated['client'] ?? '')) ?? ApiKeyClientEnum::Script,
        )->execute();

        return new ApiKeyResource($key->accessToken)
            ->additional(['token' => $key->plainTextToken])
            ->response()
            ->setStatusCode(201);
    }

    /** @return array<int, string> */
    private function scopes(bool $reachesSensitiveFields): array
    {
        return $reachesSensitiveFields
            ? ['*', ApiKeyScopeEnum::SensitiveFields->value]
            : ['*'];
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
