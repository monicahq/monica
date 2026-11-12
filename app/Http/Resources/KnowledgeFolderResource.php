<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin KnowledgeFolder */
class KnowledgeFolderResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['vault', 'notes:id,uuid,knowledge_folder_id,title', 'lists:id,uuid,knowledge_folder_id,title']);

        return [
            'type' => 'knowledge_folder',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'position' => $this->position,
                'note_count' => $this->notes->count(),
                'list_count' => $this->lists->count(),
                'notes' => $this->notes->map(fn (KnowledgeNote $note): array => ['id' => $note->uuid, 'title' => $note->title])->values()->all(),
                'lists' => $this->lists->map(fn (KnowledgeList $list): array => ['id' => $list->uuid, 'title' => $list->title])->values()->all(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'web' => route('knowledge.index', $this->vault),
            ],
        ];
    }
}
