<?php

namespace App\Settings\ManagePronouns\Web\ViewHelpers;

use App\Models\Account;
use App\Models\Pronoun;

class PersonalizePronounIndexViewHelper
{
    public static function data(Account $account): array
    {
        $pronouns = $account->pronouns()
            ->orderBy('name', 'asc')
            ->get();

        $collection = collect();
        foreach ($pronouns as $pronoun) {
            $collection->push(self::dtoPronoun($pronoun));
        }

        return [
            'pronouns' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'pronoun_store' => route('settings.personalize.pronoun.store'),
            ],
        ];
    }

    public static function dtoPronoun(Pronoun $pronoun): array
    {
        return [
            'id' => $pronoun->id,
            'name' => $pronoun->name,
            'url' => [
                'update' => route('settings.personalize.pronoun.update', [
                    'pronoun' => $pronoun->id,
                ]),
                'destroy' => route('settings.personalize.pronoun.destroy', [
                    'pronoun' => $pronoun->id,
                ]),
            ],
        ];
    }
}
