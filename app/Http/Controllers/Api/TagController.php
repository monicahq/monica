<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\Tags\CreateTagService;
use App\Services\Tags\DeleteTagService;
use App\Services\Tags\TagListService;
use App\Services\Tags\UpdateTagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function index(TagListService $service)
    {
        Gate::authorize('vault-viewer', Auth::user()->vault_id);

        return response()->json([
            'data' => $service->execute(),
        ]);
    }

    public function store(Request $request, CreateTagService $service)
    {
        Gate::authorize('vault-editor', Auth::user()->vault_id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'tag_category' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        return response()->json([
            'data' => $service->execute($data),
        ], 201);
    }

    public function update(Request $request, int $id, UpdateTagService $service)
    {
        Gate::authorize('vault-editor', Auth::user()->vault_id);

        $tag = Tag::query()
            ->where('vault_id', Auth::user()->vault_id)
            ->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'tag_category' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        return response()->json([
            'data' => $service->execute($tag, $data),
        ]);
    }

    public function destroy(int $id, DeleteTagService $service)
    {
        Gate::authorize('vault-editor', Auth::user()->vault_id);

        $tag = Tag::query()
            ->where('vault_id', Auth::user()->vault_id)
            ->findOrFail($id);

        $service->execute($tag);

        return response()->json([
            'message' => 'deleted',
        ]);
    }
}
