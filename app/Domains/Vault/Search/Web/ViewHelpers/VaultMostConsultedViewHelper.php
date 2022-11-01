<?php

namespace App\Domains\Vault\Search\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VaultMostConsultedViewHelper
{
    public static function data(Vault $vault, User $user): Collection
    {
        $records = DB::table('contact_vault_user')
            ->where('vault_id', $vault->id)
            ->where('user_id', $user->id)
            ->orderBy('number_of_views', 'desc')
            ->select('contact_id')
            ->limit(5)
            ->get()
            ->toArray();

        $contactsCollection = collect();
        foreach ($records as $record) {
            $contact = Contact::find($record->contact_id);

            $contactsCollection->push([
                'id' => $contact->id,
                'name' => $contact->name,
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ]);
        }

        return $contactsCollection;
    }
}
