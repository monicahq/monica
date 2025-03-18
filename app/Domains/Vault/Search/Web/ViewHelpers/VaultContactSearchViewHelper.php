<?php

namespace App\Domains\Vault\Search\Web\ViewHelpers;

use App\Helpers\NameHelper;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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

        // Get the current user
        $user = Auth::user();

        return $contacts->map(function (Contact $contact) use ($user): array {
            return [
                'id' => $contact->id,
                // Format the name using the NameHelper to ensure consistency
                'name' => NameHelper::formatContactName($user, $contact),
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ];
        });
    }
}
