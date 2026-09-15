<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StructureVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin StructureVersion */
class StructureVersionResource extends JsonResource
{
    /**
     * Whether the answer carries the structure the version froze. It is asked for
     * rather than inferred from what happens to be loaded, because a version that
     * has been published has walked its own pages on the way and would otherwise
     * answer with them by accident.
     */
    private bool $withStructure = false;

    public function withStructure(): self
    {
        $this->withStructure = true;

        return $this;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'structure_version',
            'id' => (string) $this->id,
            'attributes' => [
                'version_number' => $this->version_number,
                'status' => $this->status->value,
                'change_note' => $this->change_note,
                'published_at' => $this->published_at?->timestamp,
                'retired_at' => $this->retired_at?->timestamp,
                'record_count' => $this->record_count,
                'previous_version_number' => $this->previousVersion?->version_number,
                'pages' => $this->when(
                    $this->withStructure,
                    fn (): array => StructureVersionPageResource::collection($this->pages)->resolve(),
                ),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.templates.versions.show', [$this->structure->owner->uuid, $this->version_number]),
            ],
        ];
    }
}
