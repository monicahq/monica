<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityCategoryTypeController extends Controller
{
    public function index(Request $request, string $activityCategory): JsonResponse
    {
        $category = $request->user()->account->activityCategories()
            ->where('uuid', $activityCategory)
            ->firstOrFail();

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $types = $category->activityTypes()
            ->with('activityCategory')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => ActivityTypeResource::collection($types->items())->resolve(),
            'meta' => [
                'page' => $types->currentPage(),
                'per_page' => $types->perPage(),
                'total' => $types->total(),
            ],
        ]);
    }
}
