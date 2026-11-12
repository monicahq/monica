<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\KnowledgeMarkdown;
use App\Models\KnowledgeNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A note of a vault's Knowledge. Its text travels as the Markdown written, never drawn, and is left
 * out of a listing, which reads titles only.
 *
 * @mixin KnowledgeNote
 */
class KnowledgeNoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('folder.vault');

        return [
            'type' => 'knowledge_note',
            'id' => $this->uuid,
            'attributes' => [
                'title' => $this->title,
                'body' => $this->whenHas('body'),
                'headings' => $this->whenHas('body', fn (): array => KnowledgeMarkdown::headings($this->body)),
                'folder' => ['id' => $this->folder->uuid, 'name' => $this->folder->name],
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.knowledge.notes.show', [$this->folder->vault->slug, $this->uuid]),
                'web' => route('knowledge.notes.show', [$this->folder->vault, $this->resource]),
            ],
        ];
    }
}
