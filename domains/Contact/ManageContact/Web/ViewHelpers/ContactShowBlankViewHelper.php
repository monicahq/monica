<?php

namespace App\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

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
                'name' => $contact->getName(Auth::user()),
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
