<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Knowledge;

use App\Helpers\KnowledgeSearch;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeListResource;
use App\Http\Resources\KnowledgeNoteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeSearchController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $found = KnowledgeSearch::in(
            vault: $request->user()->account->vaults()->addressedBy($vault)->firstOrFail(),
            query: $request->string('query')->toString(),
        );

        return response()->json([
            'data' => [
                ...KnowledgeNoteResource::collection($found['notes'])->resolve(),
                ...KnowledgeListResource::collection($found['lists'])->resolve(),
            ],
        ]);
    }
}
