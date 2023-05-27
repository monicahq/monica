<?php

namespace App\Domains\Settings\ManagePronouns\Web\ViewHelpers;

use App\Models\Account;
use App\Models\Pronoun;

class PersonalizePronounIndexViewHelper
{
    public static function data(Account $account): array
    {
        $pronouns = $account->pronouns()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Pronoun $pronoun) => self::dtoPronoun($pronoun));

        return [
            'pronouns' => $pronouns,
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
