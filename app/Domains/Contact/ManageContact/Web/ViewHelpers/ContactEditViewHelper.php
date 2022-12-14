<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class ContactEditViewHelper
{
    public static function data(Vault $vault, Contact $contact, User $user): array
    {
        $account = $vault->account;

        $genders = $account->genders()->orderBy('name', 'asc')->get();
        $genderCollection = $genders->map(function ($gender) use ($contact) {
            return [
                'id' => $gender->id,
                'name' => $gender->name,
                'selected' => $gender->id === $contact->gender_id ? true : false,
            ];
        });

        $pronouns = $account->pronouns()->orderBy('name', 'asc')->get();
        $pronounCollection = $pronouns->map(function ($pronoun) use ($contact) {
            return [
                'id' => $pronoun->id,
                'name' => $pronoun->name,
                'selected' => $pronoun->id === $contact->pronoun_id ? true : false,
            ];
        });

        return [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'middle_name' => $contact->middle_name,
                'nickname' => $contact->nickname,
                'maiden_name' => $contact->maiden_name,
                'gender_id' => $contact->gender_id,
                'pronoun_id' => $contact->pronoun_id,
                'prefix' => $contact->prefix,
                'suffix' => $contact->suffix,
            ],
            'genders' => $genderCollection,
            'pronouns' => $pronounCollection,
            'url' => [
                'update' => route('contact.update', [
                    'vault' => $vault->id,
                    'contact' => $contact->id,
                ]),
                'show' => route('contact.show', [
                    'vault' => $vault->id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
