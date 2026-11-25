<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ValueListEnum;
use App\Models\ValueListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ValueListItem */
class ValueListItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'value_list_item',
            'id' => $this->id,
            'attributes' => [
                'label' => $this->label,
                'country_code' => $this->valueList->key === ValueListEnum::Countries->value ? $this->countryCode() : null,
                'is_active' => $this->is_active,
                'position' => $this->position,
            ],
        ];
    }
}
