<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionType */
class PossessionTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['groups.fields', 'standaloneFields']);

        $groups = $this->groups;
        $standaloneFields = $this->standaloneFields;

        return [
            'type' => 'possession_type',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'colour' => $this->colour,
                'icon' => $this->icon,
                'field_count' => $standaloneFields->count() + $groups->sum(fn (PossessionFieldGroup $group): int => $group->fields->count()),
                'groups' => $groups
                    ->map(fn (PossessionFieldGroup $group): array => [
                        'id' => $group->uuid,
                        'name' => $group->name,
                        'position' => $group->position,
                        'fields' => $group->fields->map(fn (PossessionField $field): array => $this->field($field, $group))->values()->all(),
                    ])
                    ->values()
                    ->all(),
                'fields' => $standaloneFields->map(fn (PossessionField $field): array => $this->field($field, null))->values()->all(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessionTypes.show', $this->uuid),
                'web' => route('settings.possessionTypes.edit', $this->resource),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function field(PossessionField $field, ?PossessionFieldGroup $group): array
    {
        return [
            'id' => $field->uuid,
            'name' => $field->name,
            'kind' => $field->kind->value,
            'options' => $field->options,
            'position' => $field->position,
            'group_id' => $group?->uuid,
        ];
    }
}
