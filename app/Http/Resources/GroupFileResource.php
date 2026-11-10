<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GroupFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin GroupFile */
class GroupFileResource extends JsonResource
{
    public const array RELATIONS = ['group.vault', 'file'];

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'group_file',
            'id' => $this->uuid,
            'attributes' => [
                'group' => [
                    'id' => $this->group->uuid,
                    'name' => $this->group->name,
                ],
                'name' => $this->file->original_name,
                'mime_type' => $this->file->mime_type,
                'size_in_bytes' => $this->file->size_in_bytes,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.groups.files.show', [$this->group->vault, $this->group->uuid, $this->uuid]),
                'content' => route('api.v1.groups.files.content.show', [$this->group->vault, $this->group->uuid, $this->uuid]),
            ],
        ];
    }
}
