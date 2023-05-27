<?php

namespace App\Domains\Settings\ManageGenders\Web\ViewHelpers;

use App\Models\Account;
use App\Models\Gender;

class ManageGenderIndexViewHelper
{
    public static function data(Account $account): array
    {
        $genders = $account->genders()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Gender $gender) => self::dtoGender($gender));

        return [
            'genders' => $genders,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'gender_store' => route('settings.personalize.gender.store'),
            ],
        ];
    }

    public static function dtoGender(Gender $gender): array
    {
        return [
            'id' => $gender->id,
            'name' => $gender->name,
            'url' => [
                'update' => route('settings.personalize.gender.update', [
                    'gender' => $gender->id,
                ]),
                'destroy' => route('settings.personalize.gender.destroy', [
                    'gender' => $gender->id,
                ]),
            ],
        ];
    }
}
