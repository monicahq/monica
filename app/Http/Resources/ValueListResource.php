<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ValueListEnum;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ValueList */
class ValueListResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'value_list',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'items' => $this->items
                    ->sortBy('position')
                    ->map(fn (ValueListItem $item): array => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'country_code' => $this->key === ValueListEnum::Countries->value ? $item->countryCode() : null,
                        'is_active' => $item->is_active,
                        'position' => $item->position,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
