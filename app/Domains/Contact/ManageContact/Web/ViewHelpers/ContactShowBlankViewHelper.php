<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Contact;

class ContactShowBlankViewHelper
{
    public static function data(Contact $contact): array
    {
        $templates = $contact->vault->account->templates;

        $templatesCollection = $templates->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
            ];
        });

        return [
            'templates' => $templatesCollection,
            'contact' => [
                'name' => $contact->name,
            ],
            'url' => [
                'update' => route('contact.template.update', [
                    'vault' => $contact->vault->id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
