<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Label;
use App\Models\User;
use App\Models\Vault;

class ContactIndexViewHelper
{
    public static function data($contacts, Vault $vault, ?int $labelId, User $user): array
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
            ->withCount('contacts')
            ->get()
            ->sortByCollator('name')
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
            'user_contact_sort_order' => $user->contact_sort_order,
            'contact_sort_orders' => collect([
                [
                    'id' => 'last_updated',
                    'name' => trans('By last updated'),
                ],
                [
                    'id' => 'asc',
                    'name' => trans('From A to Z'),
                ],
                [
                    'id' => 'desc',
                    'name' => trans('From Z to A'),
                ],
            ]),
            'url' => [
                'contact' => [
                    'index' => route('contact.index', [
                        'vault' => $vault->id,
                    ]),
                    'create' => route('contact.create', [
                        'vault' => $vault->id,
                    ]),
                ],
                'sort' => [
                    'update' => route('contact.sort.update', [
                        'vault' => $vault->id,
                    ]),
                ],
            ],
        ];
    }
}
