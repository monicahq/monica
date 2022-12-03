<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Label;
use App\Models\Vault;

class ContactIndexViewHelper
{
    public static function data($contacts, Vault $vault, int $labelId = null): array
    {
        $contactCollection = collect();
        foreach ($contacts as $contact) {
            $contactCollection->push([
                'id' => $contact->id,
                'name' => $contact->name,
                'avatar' => $contact->avatar,
                'url' => [
                    'show' => route('contact.show', [
                        'vault' => $vault->id,
                        'contact' => $contact->id,
                    ]),
                ],
            ]);
        }

        $labelsCollection = $vault->labels()
            ->orderBy('name', 'asc')
            ->withCount('contacts')
            ->get()
            ->filter(fn (Label $label): bool => $label->contacts_count > 0)
            ->map(fn (Label $label) => [
                'id' => $label->id,
                'name' => $label->name,
                'count' => $label->contacts_count,
                'url' => [
                    'show' => route('contact.label.index', [
                        'vault' => $label->vault_id,
                        'label' => $label->id,
                    ]),
                ],
            ]);

        return [
            'contacts' => $contactCollection,
            'labels' => $labelsCollection,
            'current_label' => $labelId,
            'url' => [
                'contact' => [
                    'index' => route('contact.index', [
                        'vault' => $vault->id,
                    ]),
                    'create' => route('contact.create', [
                        'vault' => $vault->id,
                    ]),
                ],
            ],
        ];
    }
}
