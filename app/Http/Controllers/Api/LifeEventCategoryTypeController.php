<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LifeEventCategoryTypeController extends Controller
{
    public function index(Request $request, string $lifeEventCategory): JsonResponse
    {
        $category = $request->user()->account->lifeEventCategories()
            ->where('uuid', $lifeEventCategory)
            ->firstOrFail();

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $types = $category->lifeEventTypes()
            ->with('lifeEventCategory')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => LifeEventTypeResource::collection($types->items())->resolve(),
            'meta' => [
                'page' => $types->currentPage(),
                'per_page' => $types->perPage(),
                'total' => $types->total(),
            ],
        ]);
    }
}
