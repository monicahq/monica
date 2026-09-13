<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelationshipCategoryTypeController extends Controller
{
    public function index(Request $request, string $relationshipCategory): JsonResponse
    {
        $category = $request->user()->account->relationshipCategories()
            ->where('uuid', $relationshipCategory)
            ->firstOrFail();

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $types = $category->types()
            ->with('relationshipCategory')
            ->withCount('relationships')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => RelationshipTypeResource::collection($types->items())->resolve(),
            'meta' => [
                'page' => $types->currentPage(),
                'per_page' => $types->perPage(),
                'total' => $types->total(),
            ],
        ]);
    }
}
