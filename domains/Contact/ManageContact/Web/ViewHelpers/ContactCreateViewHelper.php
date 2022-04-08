<?php

namespace App\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Vault;

class ContactCreateViewHelper
{
    public static function data(Vault $vault): array
    {
        $account = $vault->account;

        $genders = $account->genders()->orderBy('name', 'asc')->get();
        $genderCollection = $genders->map(function ($gender) {
            return [
                'id' => $gender->id,
                'name' => $gender->name,
            ];
        });

        $pronouns = $account->pronouns()->orderBy('name', 'asc')->get();
        $pronounCollection = $pronouns->map(function ($pronoun) {
            return [
                'id' => $pronoun->id,
                'name' => $pronoun->name,
            ];
        });

        $templates = $account->templates;
        $templateCollection = $templates->map(function ($template) use ($vault) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'selected' => $template->id === $vault->default_template_id,
            ];
        });

        return [
            'genders' => $genderCollection,
            'pronouns' => $pronounCollection,
            'templates' => $templateCollection,
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
