<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\StructureVersion;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class FieldCorrespondence
{
    /** @var EloquentCollection<int, Field> */
    private EloquentCollection $carriedFields;

    /** @var Collection<array-key, Field> */
    private Collection $inTheSameSection;

    /** @var Collection<array-key, Collection<int, Field>> */
    private Collection $anywhere;

    public function __construct(StructureVersion $target)
    {
        $ids = $target->assembly
            ->load('sectionVersion.fields')
            ->flatMap(fn ($row): array => $row->sectionVersion->fields->pluck('id')->all())
            ->all();

        $this->carriedFields = Field::query()
            ->whereIn('id', $ids)
            ->with('sectionVersion')
            ->get();

        $this->inTheSameSection = $this->carriedFields->toBase()->keyBy(fn (Field $field): string => $this->sameSectionKey($field));
        $this->anywhere = $this->carriedFields->toBase()->groupBy(fn (Field $field): string => "{$field->parent_field_id}|{$field->key}|{$field->type->value}");
    }

    /** @return list<int> */
    public function carriedFieldIds(): array
    {
        return array_values($this->carriedFields->modelKeys());
    }

    /** @return array<int, Field> */
    public function counterpartsFor(Contact|Entity $record): array
    {
        $values = FieldValue::query()
            ->where('valuable_type', $record->getMorphClass())
            ->where('valuable_id', $record->id);

        $rows = FieldValueRow::query()
            ->where('valuable_type', $record->getMorphClass())
            ->where('valuable_id', $record->id);

        $carried = $this->carriedFieldIds();

        $heldIds = $values->clone()->pluck('field_id')
            ->merge($rows->clone()->pluck('field_id'))
            ->unique()
            ->diff($carried);

        if ($heldIds->isEmpty()) {
            return [];
        }

        $heldFields = Field::query()
            ->whereIn('id', $heldIds)
            ->with('sectionVersion')
            ->get();

        $filled = $values->clone()->whereIn('field_id', $carried)->pluck('field_id')
            ->merge($rows->clone()->whereIn('field_id', $carried)->pluck('field_id'))
            ->all();

        return $this->counterparts($heldFields, $filled);
    }

    /**
     * @param  Collection<int, Field>  $heldFields
     * @param  array<int, int>  $filledFieldIds
     * @return array<int, Field>
     */
    public function counterparts(Collection $heldFields, array $filledFieldIds): array
    {
        $heldFields = $heldFields->sortBy(fn (Field $held): bool => $held->parent_field_id !== null);

        $wanted = $heldFields
            ->reject(fn (Field $held): bool => $this->inTheSameSection->has($this->sameSectionKey($held)))
            ->countBy(fn (Field $held): string => "{$held->parent_field_id}|{$held->key}|{$held->type->value}");

        /** @var array<int, true> $taken */
        $taken = collect($filledFieldIds)
            ->merge($heldFields->map(fn (Field $held): ?int => $this->inTheSameSection->get($this->sameSectionKey($held))?->id)->filter())
            ->mapWithKeys(fn (int $id): array => [$id => true])
            ->all();

        /** @var array<int, Field> $followed */
        $followed = [];

        foreach ($heldFields as $held) {
            $counterpart = $this->inTheSameSection->get($this->sameSectionKey($held));

            if (! $counterpart instanceof Field) {
                if ($wanted->get("{$held->parent_field_id}|{$held->key}|{$held->type->value}") > 1) {
                    continue;
                }

                $parent = $held->parent_field_id === null ? null : ($followed[$held->parent_field_id] ?? null);

                if ($held->parent_field_id !== null && $parent === null) {
                    continue;
                }

                $candidates = $this->anywhere->get("{$parent?->id}|{$held->key}|{$held->type->value}");

                if ($candidates === null || $candidates->count() !== 1) {
                    continue;
                }

                $counterpart = $candidates->first();

                if (isset($taken[$counterpart->id])) {
                    continue;
                }

                $taken[$counterpart->id] = true;
            }

            $followed[$held->id] = $counterpart;
        }

        return $followed;
    }

    private function sameSectionKey(Field $field): string
    {
        return "{$field->sectionVersion->section_id}|{$field->key}|{$field->type->value}";
    }
}
