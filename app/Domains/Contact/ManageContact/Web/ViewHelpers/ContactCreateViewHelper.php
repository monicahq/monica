<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Gender;
use App\Models\Pronoun;
use App\Models\Template;
use App\Models\Vault;

class ContactCreateViewHelper
{
    public static function data(Vault $vault): array
    {
        $account = $vault->account;

        $genders = $account->genders()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Gender $gender) => [
                'id' => $gender->id,
                'name' => $gender->name,
            ]);

        $pronouns = $account->pronouns()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Pronoun $pronoun) => [
                'id' => $pronoun->id,
                'name' => $pronoun->name,
            ]);

        $templates = $account->templates
            ->sortByCollator('name')
            ->map(fn (Template $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'selected' => $template->id === $vault->default_template_id,
            ]);

        return [
            'genders' => $genders,
            'pronouns' => $pronouns,
            'templates' => $templates,
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
