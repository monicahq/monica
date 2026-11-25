<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\PossessionFieldValueFormat;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionFieldValue;
use App\Models\PossessionTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Possession */
class PossessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['vault', 'possessionType.groups.fields', 'possessionType.standaloneFields', 'location', 'values', 'transactions', 'tags', 'valuations']);
        $currentValue = $this->currentValue();
        $valueSource = ($currentValue['isValuation'] ?? false) ? 'valuation' : 'declared_value';
        $acquisition = $this->acquisitionTransaction();

        return [
            'type' => 'possession',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'identifier' => $this->identifier,
                'status' => $this->status->value,
                'quantity' => $this->quantity,
                'is_featured' => $this->is_featured,
                'position' => $this->position,
                'possession_type' => [
                    'id' => $this->possessionType->uuid,
                    'name' => $this->possessionType->name,
                    'colour' => $this->possessionType->colour,
                    'icon' => $this->possessionType->icon,
                ],
                'location' => $this->location === null ? null : [
                    'id' => $this->location->uuid,
                    'name' => $this->location->name,
                ],
                'values' => $this->values(),
                'acquisition' => [
                    'way' => $acquisition?->type->value,
                    'acquired_from' => $acquisition?->counterparty === null ? null : [
                        'name' => $acquisition->counterparty,
                    ],
                    'acquired_on' => $acquisition?->occurred_on?->format('Y-m-d'),
                    'transaction_id' => $acquisition?->uuid,
                    'declared_value' => $this->declared_value === null ? null : [
                        'amount' => $this->declared_value,
                        'currency' => $this->declared_value_currency,
                    ],
                ],
                'current_value' => $currentValue === null ? null : [
                    'amount' => $currentValue['amount'],
                    'currency' => $currentValue['currency'],
                    'source' => $valueSource,
                ],
                'tags' => $this->tags->map(fn (PossessionTag $tag): array => [
                    'id' => $tag->uuid,
                    'name' => $tag->name,
                    'colour' => $tag->colour,
                ])->all(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.show', [$this->vault, $this->resource]),
            ],
        ];
    }

    /**
     * The values the possession holds, in the order of the fields of its type: the groups first,
     * then the standalone fields.
     *
     * @return list<array{field_id: string, name: string, kind: string, group: string|null, value: string|int|float|bool}>
     */
    private function values(): array
    {
        $held = $this->resource->values->keyBy('possession_field_id');
        $type = $this->possessionType;

        $fields = $type->groups
            ->flatMap(fn (PossessionFieldGroup $group) => $group->fields->map(fn (PossessionField $field): array => [$field, $group->name]))
            ->concat($type->standaloneFields->map(fn (PossessionField $field): array => [$field, null]));

        return $fields
            ->filter(fn (array $pair): bool => $held->has($pair[0]->id))
            ->map(function (array $pair) use ($held): array {
                /** @var PossessionField $field */
                [$field, $group] = $pair;

                /** @var PossessionFieldValue $value */
                $value = $held->get($field->id);

                return [
                    'field_id' => $field->uuid,
                    'name' => $field->name,
                    'kind' => $field->kind->value,
                    'group' => $group,
                    'value' => PossessionFieldValueFormat::read($field->kind, $value->value),
                ];
            })
            ->values()
            ->all();
    }
}
