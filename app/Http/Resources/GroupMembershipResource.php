<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin GroupMember */
class GroupMembershipResource extends JsonResource
{
    public const array RELATIONS = ['group.vault', 'contact'];

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'group_membership',
            'id' => $this->uuid,
            'attributes' => [
                'group' => [
                    'id' => $this->group->uuid,
                    'name' => $this->group->name,
                ],
                'contact' => [
                    'id' => $this->contact->uuid,
                    'slug' => $this->contact->slug,
                    'name' => $this->contact->name,
                ],
                'role' => $this->role,
                'joined_on' => $this->joined_on?->format('Y-m-d'),
                'joined_precision' => $this->joined_precision->value,
                'left_on' => $this->left_on?->format('Y-m-d'),
                'left_precision' => $this->left_precision->value,
                'is_current' => $this->isCurrent(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
        ];
    }
}
