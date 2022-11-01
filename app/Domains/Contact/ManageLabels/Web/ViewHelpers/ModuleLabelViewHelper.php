<?php

namespace App\Domains\Contact\ManageLabels\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Label;

class ModuleLabelViewHelper
{
    public static function data(Contact $contact): array
    {
        $labelsInVault = $contact->vault->labels;
        $labelsInContact = $contact->labels;

        $labelsInVaultCollection = $labelsInVault->map(function ($label) use ($contact, $labelsInContact) {
            $taken = false;
            if ($labelsInContact->contains($label)) {
                $taken = true;
            }

            return self::dtoLabel($label, $contact, $taken);
        });

        $labelsAssociatedWithContactCollection = $labelsInContact->map(function ($label) use ($contact) {
            return self::dtoLabel($label, $contact);
        });

        return [
            'labels_in_contact' => $labelsAssociatedWithContactCollection,
            'labels_in_vault' => $labelsInVaultCollection,
            'url' => [
                'store' => route('contact.label.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update' => route('contact.date.index', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dtoLabel(Label $label, Contact $contact, bool $taken = false): array
    {
        return [
            'id' => $label->id,
            'name' => $label->name,
            'bg_color' => $label->bg_color,
            'text_color' => $label->text_color,
            'taken' => $taken,
            'url' => [
                'show' => route('contact.label.index', [
                    'vault' => $contact->vault_id,
                    'label' => $label->id,
                ]),
                'update' => route('contact.label.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'label' => $label->id,
                ]),
                'destroy' => route('contact.label.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'label' => $label->id,
                ]),
            ],
        ];
    }
}
