<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A list of a vault's Knowledge, with its columns and its items when they were loaded. Each item
 * holds its texts keyed by the uuid of their column, a blank cell left out.
 *
 * @mixin KnowledgeList
 */
class KnowledgeListResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('folder.vault');

        return [
            'type' => 'knowledge_list',
            'id' => $this->uuid,
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
                'folder' => ['id' => $this->folder->uuid, 'name' => $this->folder->name],
                'columns' => $this->whenLoaded('columns', fn (): array => $this->columns
                    ->map(fn (KnowledgeListColumn $column): array => ['id' => $column->uuid, 'name' => $column->name, 'position' => $column->position])
                    ->values()
                    ->all()),
                'items' => $this->whenLoaded('items', fn (): array => $this->items
                    ->map(fn (KnowledgeListItem $item): array => [
                        'id' => $item->uuid,
                        'position' => $item->position,
                        'cells' => (object) $item->cells
                            ->mapWithKeys(fn (KnowledgeListCell $cell): array => [(string) $this->columns->firstWhere('id', $cell->knowledge_list_column_id)?->uuid => $cell->value])
                            ->all(),
                    ])
                    ->values()
                    ->all()),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.knowledge.lists.show', [$this->folder->vault->slug, $this->uuid]),
                'web' => route('knowledge.lists.show', [$this->folder->vault, $this->resource]),
            ],
        ];
    }
}
