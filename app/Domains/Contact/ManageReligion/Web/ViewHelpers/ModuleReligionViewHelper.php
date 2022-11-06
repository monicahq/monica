<?php

namespace App\Domains\Contact\ManageReligion\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Religion;
use Illuminate\Support\Collection;

class ModuleReligionViewHelper
{
    public static function data(Contact $contact): array
    {
        return [
            'religion' => $contact->religion ? [
                'id' => $contact->religion->id,
                'name' => $contact->religion->name,
            ] : null,
            'religions' => self::list($contact),
            'url' => [
                'update' => route('contact.religion.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function list(Contact $contact): Collection
    {
        return $contact->vault->account
            ->religions()
            ->orderBy('position', 'asc')
            ->get()
            ->map(fn (Religion $religion) => self::dto($religion, $contact));
    }

    public static function dto(Religion $religion, Contact $contact): array
    {
        return [
            'id' => $religion->id,
            'name' => $religion->name,
            'selected' => $religion->id === $contact->religion_id,
        ];
    }
}
