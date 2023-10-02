<?php

namespace App\Domains\Contact\ManageQuickFacts\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\QuickFact;
use App\Models\VaultQuickFactsTemplate;

class ContactModuleQuickFactViewHelper
{
    public static function data(Contact $contact, VaultQuickFactsTemplate $template): array
    {
        $quickFacts = $contact->quickFacts()
            ->where('vault_quick_facts_template_id', $template->id)
            ->get()
            ->map(fn (QuickFact $quickFact) => self::dto($quickFact));

        return [
            'template' => [
                'id' => $template->id,
                'label' => $template->label,
                'url' => [
                    'store' => route('contact.quick_fact.store', [
                        'vault' => $contact->vault_id,
                        'contact' => $contact->id,
                        'template' => $template->id,
                    ]),
                ],
            ],
            'quick_facts' => $quickFacts,
        ];
    }

    public static function dto(QuickFact $quickFact): array
    {
        $contact = $quickFact->contact;

        return [
            'id' => $quickFact->id,
            'content' => $quickFact->content,
            'url' => [
                'update' => route('contact.quick_fact.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'template' => $quickFact->vault_quick_facts_template_id,
                    'quickFact' => $quickFact->id,
                ]),
                'destroy' => route('contact.quick_fact.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'template' => $quickFact->vault_quick_facts_template_id,
                    'quickFact' => $quickFact->id,
                ]),
            ],
        ];
    }
}
