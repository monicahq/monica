<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\McpCallResultEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\McpCallResource;
use App\Models\McpCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class McpCallController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['nullable', Rule::in(array_keys(McpCall::PERIODS))],
            'connection' => ['nullable', 'string', 'regex:/^(api_key|oauth_client):.+$/'],
            'result' => ['nullable', Rule::enum(McpCallResultEnum::class)],
            'tool' => ['nullable', 'string', 'max:255'],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $calls = $request->user()->account->mcpCalls()
            ->with('vault')
            ->matching(
                $request->filled('period') ? $request->string('period')->toString() : null,
                $request->filled('connection') ? $request->string('connection')->toString() : null,
                $request->filled('result') ? $request->string('result')->toString() : null,
                $request->filled('tool') ? $request->string('tool')->toString() : null,
            )
            ->latest('started_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => McpCallResource::collection($calls->items())->resolve(),
            'meta' => [
                'page' => $calls->currentPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
            ],
        ]);
    }

    public function show(Request $request, string $mcpCall): JsonResponse
    {
        $call = $request->user()->account->mcpCalls()
            ->with('vault')
            ->where('uuid', $mcpCall)
            ->firstOrFail();

        return new McpCallResource($call)->response()->setStatusCode(200);
    }
}
