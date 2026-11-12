<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Knowledge;

use App\Actions\CreateKnowledgeListColumn;
use App\Actions\DestroyKnowledgeListColumn;
use App\Actions\UpdateKnowledgeListColumn;
use App\Actions\UpdateKnowledgeListColumnPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeListResource;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * The columns of a list. Adding or changing one answers with the whole list, since a column means
 * nothing without the items reading it.
 */
class KnowledgeListColumnController extends Controller
{
    public function create(Request $request, string $vault, string $list): JsonResponse
    {
        $knowledgeList = $this->list($request, $vault, $list);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new CreateKnowledgeListColumn(
            user: $request->user(),
            list: $knowledgeList,
            name: $request->string('name')->toString(),
        )->execute();

        return new KnowledgeListResource($knowledgeList->refresh()->load('columns', 'items.cells'))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $list, string $column): JsonResponse
    {
        $knowledgeList = $this->list($request, $vault, $list);
        $knowledgeListColumn = $this->column($knowledgeList, $column);

        $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        if ($request->exists('name')) {
            new UpdateKnowledgeListColumn(
                user: $request->user(),
                column: $knowledgeListColumn,
                name: $request->string('name')->toString(),
            )->execute();
        }

        if ($request->exists('position')) {
            new UpdateKnowledgeListColumnPosition(
                user: $request->user(),
                column: $knowledgeListColumn,
                position: $request->integer('position'),
            )->execute();
        }

        return new KnowledgeListResource($knowledgeList->refresh()->load('columns', 'items.cells'))->response();
    }

    public function destroy(Request $request, string $vault, string $list, string $column): Response
    {
        $knowledgeList = $this->list($request, $vault, $list);

        try {
            new DestroyKnowledgeListColumn(
                user: $request->user(),
                column: $this->column($knowledgeList, $column),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['column' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function list(Request $request, string $vault, string $uuid): KnowledgeList
    {
        $record = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        return KnowledgeList::query()
            ->where('vault_id', $record->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function column(KnowledgeList $list, string $uuid): KnowledgeListColumn
    {
        return KnowledgeListColumn::query()
            ->where('knowledge_list_id', $list->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
