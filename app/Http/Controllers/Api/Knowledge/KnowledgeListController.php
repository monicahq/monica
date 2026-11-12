<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Knowledge;

use App\Actions\CreateKnowledgeList;
use App\Actions\DestroyKnowledgeList;
use App\Actions\UpdateKnowledgeList;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeListResource;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KnowledgeListController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $record = $this->vault($request, $vault);

        $request->validate([
            'folder' => ['nullable', 'uuid'],
        ]);

        $lists = KnowledgeList::query()
            ->where('vault_id', $record->id)
            ->when($request->filled('folder'), fn ($query) => $query->where('knowledge_folder_id', $this->folder($record, $request->string('folder')->toString())->id))
            ->with('folder.vault')
            ->orderBy('title')
            ->get();

        return response()->json([
            'data' => KnowledgeListResource::collection($lists)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $list): JsonResponse
    {
        $knowledgeList = $this->list($this->vault($request, $vault), $list);
        $knowledgeList->load('columns', 'items.cells');

        return new KnowledgeListResource($knowledgeList)->response();
    }

    public function create(Request $request, string $vault): JsonResponse
    {
        $record = $this->vault($request, $vault);

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.KnowledgeList::MAX_DESCRIPTION_LENGTH],
            'folder_id' => ['nullable', 'uuid'],
        ]);

        $list = new CreateKnowledgeList(
            user: $request->user(),
            vault: $record,
            folder: $request->filled('folder_id') ? $this->folder($record, $request->string('folder_id')->toString()) : null,
            title: $request->string('title')->toString(),
            description: $request->string('description')->toString(),
        )->execute();

        $list->load('columns', 'items.cells');

        return new KnowledgeListResource($list)->response()->setStatusCode(201);
    }

    /**
     * Change a list. A field left out of the request keeps what the list says; a description sent as
     * null or empty is cleared.
     */
    public function update(Request $request, string $vault, string $list): JsonResponse
    {
        $record = $this->vault($request, $vault);
        $knowledgeList = $this->list($record, $list);

        $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.KnowledgeList::MAX_DESCRIPTION_LENGTH],
            'folder_id' => ['sometimes', 'uuid'],
        ]);

        new UpdateKnowledgeList(
            user: $request->user(),
            list: $knowledgeList,
            title: $request->exists('title') ? $request->string('title')->toString() : null,
            description: $request->exists('description') ? $request->string('description')->toString() : null,
            folder: $request->filled('folder_id') ? $this->folder($record, $request->string('folder_id')->toString()) : null,
        )->execute();

        $knowledgeList->refresh()->load('columns', 'items.cells');

        return new KnowledgeListResource($knowledgeList)->response();
    }

    public function destroy(Request $request, string $vault, string $list): Response
    {
        new DestroyKnowledgeList(
            user: $request->user(),
            list: $this->list($this->vault($request, $vault), $list),
        )->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function folder(Vault $vault, string $uuid): KnowledgeFolder
    {
        return KnowledgeFolder::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function list(Vault $vault, string $uuid): KnowledgeList
    {
        return KnowledgeList::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
