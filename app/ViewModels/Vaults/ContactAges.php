<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DefaultFieldEnum;
use App\Models\Contact;
use App\Models\FieldValue;

class ContactAges
{
    /**
     * How old each of these people is, asked for all of them at once so a long
     * list costs the same as a short one.
     *
     * A birthday is a field somebody's template may or may not ask for, so most
     * of them will have no row here at all. One recorded without a year is left
     * out too: the column still holds a date, and it is a placeholder that must
     * never reach an age.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    public static function of(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $birthdays = FieldValue::query()
            ->attached()
            ->where('valuable_type', Contact::class)
            ->whereIn('valuable_id', $ids)
            ->where('field_key', DefaultFieldEnum::Birthday->value)
            ->whereNull('field_value_row_id')
            ->where('year_is_unknown', false)
            ->whereNotNull('value_date')
            ->get(['valuable_id', 'value_date']);

        $ages = [];

        foreach ($birthdays as $birthday) {
            if ($birthday->value_date === null) {
                continue;
            }

            $ages[$birthday->valuable_id] = $birthday->value_date->age;
        }

        return $ages;
    }
}
