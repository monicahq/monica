<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\SupportQueue;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SupportRequestResource;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $queue = new SupportQueue(
            view: (string) $request->string('view'),
            search: (string) $request->string('q'),
            category: (string) $request->string('category'),
            user: (string) $request->string('user'),
            age: (string) $request->string('age'),
        );

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $supportRequests = $queue->query()->with('user')->paginate($perPage);

        return response()->json([
            'data' => array_map(
                fn (SupportRequest $supportRequest): array => new SupportRequestResource($supportRequest)->resolve($request),
                $supportRequests->items(),
            ),
            'meta' => [
                'page' => $supportRequests->currentPage(),
                'per_page' => $supportRequests->perPage(),
                'total' => $supportRequests->total(),
                'counts' => $queue->counts(),
            ],
        ]);
    }

    public function show(Request $request, string $supportRequest): JsonResponse
    {
        $supportRequest = SupportRequest::query()
            ->where('uuid', $supportRequest)
            ->with(['user', 'messages.attachments', 'statusChanges'])
            ->firstOrFail();

        return new SupportRequestResource($supportRequest)->response()->setStatusCode(200);
    }
}
