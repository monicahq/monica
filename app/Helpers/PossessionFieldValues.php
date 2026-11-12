<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The values a possession holds for the fields of its type, given keyed by the uuid of the field, on
 * the form, through the API and through the MCP server alike. Every value is checked against its
 * field before any is written, and an empty value means the field holds nothing.
 */
class PossessionFieldValues
{
    /**
     * @param  array<string, mixed>  $values  keyed by the uuid of the field
     * @return Collection<string, PossessionField>
     */
    public static function fields(int $possessionTypeId, array $values): Collection
    {
        $fields = PossessionField::query()
            ->where('possession_type_id', $possessionTypeId)
            ->whereIn('uuid', array_map(strval(...), array_keys($values)))
            ->get()
            ->keyBy('uuid');

        $refusals = [];

        foreach ($values as $uuid => $value) {
            $field = $fields->get((string) $uuid);

            if ($field === null) {
                $refusals["values.{$uuid}"] = __('This field is not a field of the type of this possession.');

                continue;
            }

            if (PossessionFieldValueFormat::isEmpty($value)) {
                continue;
            }

            $refusal = PossessionFieldValueFormat::refusal($field, $value);

            if ($refusal !== null) {
                $refusals["values.{$uuid}"] = $refusal;
            }
        }

        if ($refusals !== []) {
            throw ValidationException::withMessages($refusals);
        }

        return $fields;
    }

    /**
     * @param  Collection<string, PossessionField>  $fields  as returned by fields()
     * @param  array<string, mixed>  $values  keyed by the uuid of the field
     */
    public static function write(Possession $possession, Collection $fields, array $values): void
    {
        foreach ($values as $uuid => $value) {
            /** @var PossessionField $field */
            $field = $fields->get((string) $uuid);

            if (PossessionFieldValueFormat::isEmpty($value)) {
                PossessionFieldValue::query()
                    ->where('possession_id', $possession->id)
                    ->where('possession_field_id', $field->id)
                    ->delete();

                continue;
            }

            PossessionFieldValue::query()->updateOrCreate(
                ['possession_id' => $possession->id, 'possession_field_id' => $field->id],
                ['account_id' => $possession->account_id, 'value' => PossessionFieldValueFormat::normalise($field, $value)],
            );
        }

        $possession->unsetRelation('values');
    }
}
