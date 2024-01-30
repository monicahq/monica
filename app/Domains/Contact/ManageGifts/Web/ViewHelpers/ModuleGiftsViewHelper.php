<?php

namespace App\Domains\Contact\ManageGifts\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\UserHelper;
use App\Models\Contact;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Support\Str;

class ModuleGiftsViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $gifts = $contact->gifts()->orderBy('created_at', 'desc')->take(3)->get();
        $giftsCollection = $gifts->map(function ($gift) use ($contact, $user) {
            return self::dto($contact, $gift, $user);
        });

        return [
            'gifts' => $giftsCollection,
            'url' => [
                'store' => route('contact.gift.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'index' => route('contact.gift.index', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, Gift $gift, User $user): array
    {
        return [
            'id' => $gift->id,
            'type' => $gift->type,
            'name' => $gift->name,
            'description' => $gift->description,
            'body_excerpt' => Str::length($gift->description) >= 200 ? Str::limit($gift->description, 200) : null,
            'show_full_content' => false,

            'author' => $gift->author ? UserHelper::getInformationAboutContact($gift->author, $contact->vault) : null,
            'written_at' => DateHelper::format($gift->created_at, $user),
            'url' => [
                'update' => route('contact.gift.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'gift' => $gift->id,
                ]),
                'destroy' => route('contact.gift.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'gift' => $gift->id,
                ]),
            ],
        ];
    }
}
