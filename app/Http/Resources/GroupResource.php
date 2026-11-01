<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Group */
class GroupResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'group',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'started_on' => $this->started_on?->format('Y-m-d'),
                'started_precision' => $this->started_precision->value,
                'ended_on' => $this->ended_on?->format('Y-m-d'),
                'ended_precision' => $this->ended_precision->value,
                'current_member_count' => $this->current_members_count ?? $this->currentMembers()->count(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
                'deleted_at' => $this->deleted_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.groups.show', [$this->vault, $this->uuid]),
            ],
        ];
    }
}
