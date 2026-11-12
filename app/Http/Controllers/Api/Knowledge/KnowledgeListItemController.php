<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Knowledge;

use App\Actions\CreateKnowledgeListItem;
use App\Actions\DestroyKnowledgeListItem;
use App\Actions\UpdateKnowledgeListItem;
use App\Helpers\KnowledgeListCells;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeListResource;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The items of a list. Adding or changing one answers with the whole list, its new item in place.
 */
class KnowledgeListItemController extends Controller
{
    public function create(Request $request, string $vault, string $list): JsonResponse
    {
        $knowledgeList = $this->list($request, $vault, $list);

        new CreateKnowledgeListItem(
            user: $request->user(),
            list: $knowledgeList,
            cells: $this->cells($request),
        )->execute();

        return new KnowledgeListResource($knowledgeList->refresh()->load('columns', 'items.cells'))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $list, string $item): JsonResponse
    {
        $knowledgeList = $this->list($request, $vault, $list);

        new UpdateKnowledgeListItem(
            user: $request->user(),
            item: $this->item($knowledgeList, $item),
            cells: $this->cells($request),
        )->execute();

        return new KnowledgeListResource($knowledgeList->refresh()->load('columns', 'items.cells'))->response();
    }

    public function destroy(Request $request, string $vault, string $list, string $item): Response
    {
        new DestroyKnowledgeListItem(
            user: $request->user(),
            item: $this->item($this->list($request, $vault, $list), $item),
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, string|null> */
    private function cells(Request $request): array
    {
        $request->validate([
            'cells' => ['required', 'array'],
            'cells.*' => ['nullable', 'string', 'max:'.KnowledgeListCells::MAX_LENGTH],
        ]);

        return collect($request->array('cells'))
            ->mapWithKeys(fn (mixed $value, int|string $uuid): array => [(string) $uuid => is_string($value) ? $value : null])
            ->all();
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

    private function item(KnowledgeList $list, string $uuid): KnowledgeListItem
    {
        return KnowledgeListItem::query()
            ->where('knowledge_list_id', $list->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
