<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\FieldValueFormatter;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Override;

/** @mixin Entity */
class EntityResource extends JsonResource
{
    public bool $withSensitive = false;

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $reader = $request->user() ?? Auth::user();

        if (! $reader instanceof User) {
            throw new AuthenticationException;
        }

        $formatter = new FieldValueFormatter($reader);

        return [
            'type' => 'entity',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'entity_type_id' => $this->entityType->uuid,
                'initials' => $this->initials,
                'avatar_colour' => $this->avatar_colour,
                'avatar' => $this->avatarLocation(),
                'is_archived' => $this->is_archived,
                'structure_version_id' => $this->structure_version_id,
                'template_name' => $this->structureVersion->structure->owner?->name,
                'fields' => $this->fieldValues
                    ->reject(fn (FieldValue $value): bool => $value->is_detached)
                    ->map(fn (FieldValue $value): array => [
                        'field_id' => (string) $value->field_id,
                        'key' => $value->field->key,
                        'label' => $value->field->label,
                        'type' => $value->field->type->value,
                        'row' => $value->field_value_row_id,
                        'value' => $value->field->is_sensitive && ! $this->withSensitive ? null : $formatter->format($value),
                        'is_hidden' => $value->field->is_sensitive && ! $this->withSensitive,
                    ])
                    ->values()
                    ->all(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.entities.show', [$this->vault, $this->resource]),
                'web' => route('entities.show', ['vault' => $this->vault, 'entityType' => $this->entityType, 'entity' => $this->resource]),
            ],
        ];
    }

    /** @return array{id: string, url: string}|null */
    private function avatarLocation(): ?array
    {
        if ($this->resource->avatar === null) {
            return null;
        }

        return [
            'id' => $this->resource->avatar->uuid,
            'url' => route('files.show', ['vault' => $this->vault, 'file' => $this->resource->avatar]),
        ];
    }
}
