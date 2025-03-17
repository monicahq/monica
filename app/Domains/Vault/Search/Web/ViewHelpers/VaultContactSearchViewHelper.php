<?php

namespace App\Domains\Vault\Search\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Support\Collection;

class VaultContactSearchViewHelper
{
    public static function data(Vault $vault, string $term): Collection
    {
        /** @var Collection<int, Contact> */
        $contacts = Contact::search($term)
            ->where('vault_id', $vault->id)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->take(5)
            ->get();

        return $contacts->map(function (Contact $contact): array {
            return [
                'id' => $contact->id,
                'name' => $contact->first_name.' '.$contact->last_name.' '.$contact->nickname.' '.$contact->maiden_name.' '.$contact->middle_name,
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ];
        });
    }
}
