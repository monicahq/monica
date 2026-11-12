<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\LocationTree;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Location */
class LocationResource extends JsonResource
{
    public function __construct(
        Location $resource,
        private readonly LocationTree $tree,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $holder = $this->tree->addressHolder($this->resource);

        return [
            'type' => 'location',
            'id' => $this->uuid,
            'attributes' => [
                'parent_id' => $this->tree->parentOf($this->resource)?->uuid,
                'name' => $this->name,
                'emoji' => $this->emoji,
                'address' => $this->address,
                'path' => $this->tree->path($this->resource),
                'effective_address' => $holder instanceof Location ? [
                    'address' => $holder->address,
                    'location_id' => $holder->uuid,
                    'location_name' => $holder->name,
                ] : null,
                'location_count' => $this->tree->childCount($this->resource),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.locations.show', $this->uuid),
                'web' => route('settings.locations.edit', $this->resource),
            ],
        ];
    }
}
