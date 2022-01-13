<?php

namespace App\Http\Controllers\Vault\Contact\ViewHelpers;

use App\Models\Vault;

class ContactCreateViewHelper
{
    public static function data(Vault $vault): array
    {
        $account = $vault->account;

        $genders = $account->genders()->orderBy('name', 'asc')->get();
        $genderCollection = collect();
        foreach ($genders as $gender) {
            $genderCollection->push([
                'id' => $gender->id,
                'name' => $gender->name,
            ]);
        }

        $pronouns = $account->pronouns()->orderBy('name', 'asc')->get();
        $pronounCollection = collect();
        foreach ($pronouns as $pronoun) {
            $pronounCollection->push([
                'id' => $pronoun->id,
                'name' => $pronoun->name,
            ]);
        }

        return [
            'genders' => $genderCollection,
            'pronouns' => $pronounCollection,
            'url' => [
                'store' => route('contact.store', [
                    'vault' => $vault->id,
                ]),
                'back' => route('contact.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}
