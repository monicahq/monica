<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\TrashItemKindEnum;
use App\Helpers\TrashItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin TrashItem */
class TrashItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $owner = $this->owner();

        return [
            'type' => 'trash_item',
            'id' => $this->uuid(),
            'attributes' => [
                'kind' => $this->kind->value,
                'title' => $this->title(),
                'type_label' => $this->typeLabel(),
                'context' => $this->context(),
                'deleted_at' => $this->deletedAt()->timestamp,
                'deleted_for_good_at' => $this->expiresAt()->timestamp,
                'deleted_with' => $owner === null ? null : [
                    'kind' => TrashItemKindEnum::fromModel($owner)->value,
                    'id' => $owner->uuid,
                    'title' => $owner->name,
                ],
                'can_be_restored' => $this->canBeRestored(),
                'obstacle' => $this->obstacle(),
            ],
        ];
    }
}
