<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GroupNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin GroupNote */
class GroupNoteResource extends JsonResource
{
    public const array RELATIONS = ['group.vault'];

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'group_note',
            'id' => $this->uuid,
            'attributes' => [
                'group' => [
                    'id' => $this->group->uuid,
                    'name' => $this->group->name,
                ],
                'body' => $this->body,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.groups.notes.show', [$this->group->vault, $this->group->uuid, $this->uuid]),
            ],
        ];
    }
}
